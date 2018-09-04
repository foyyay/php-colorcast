<?php

namespace Foyyay\ColorCast;

use Liquidpineapple\Color;
use Primal\Color\Parser;


class ColorCastException extends \Exception
{}
class InvalidConfigurationException extends ColorCastException
{}

function difference($setA, $setB)
{
    $setA = array_unique($setA);
    $setB = array_unique($setB);
    $intersection = array_intersect($setA, $setB);

    return array_diff(array_unique(array_merge($setA, $setB)), $intersection);
}

class ColorCast
{
    const KEY_SAT = 'saturation';
    const KEY_VAL = 'value';

    private $_config = array();
    private $_huePoints = array();
    private $_namedConfigs = array();

    public function __construct($config)
    {
        $this->_validateConfig($config);
    }

    public function fromHue($hueIn)
    {
        $hue = (($hueIn % 360) + 360) % 360;

        $leftHue = array_reduce($this->_huePoints, function ($acc, $next) use ($hue) {
            return $next <= $hue ? $next : $acc;
        });
        $rightHue = array_reduce(array_reverse($this->_huePoints), function ($acc, $next) use ($hue) {
            return $next >= $hue ? $next : $acc;
        });

        $numerator = $hue - $leftHue;
        $denomiator = $rightHue - $leftHue;

        $factor = 1;
        // If the denomiator is 0 that means left and right hue are the same.
        // So the left and right config will be the same so any factor from 0 -> 1 will give the same results.
        if ($denomiator != 0) {
            $factor = $numerator / $denomiator;
        }

        $leftConfig = $this->_config[$leftHue];
        $rightConfig = $this->_config[$rightHue];
        $interpolated = array();

        foreach ($this->_namedConfigs as $configName) {
            $leftSettings = $leftConfig[$configName];
            $rightSettings = $rightConfig[$configName];
            $settings = array();

            foreach (array(self::KEY_SAT, self::KEY_VAL) as $component) {
                $settings[$component] =
                    ($rightSettings[$component] - $leftSettings[$component]) * $factor +
                    $leftSettings[$component];
            }

            $newColor = Color::fromHSV($hue, $settings[self::KEY_SAT], $settings[self::KEY_VAL]);

            $interpolated[$configName] = strtolower($newColor->toHEXString());
        }

        return $interpolated;
    }

    public function fromColor($color)
    {
        $parsedColor = (new Parser($color))->getResult();
        return $this->fromHue($parsedColor->toHSV()->hue);
    }

    public function _validateConfig(array $config)
    {
        $namedConfigs = null;
        $huePointSet = array();
        $hues = array_keys($config);

        if (count($hues) < 1) {
            throw new InvalidConfigurationException('Must have at least one hue in the configuration.');
        }

        foreach ($hues as $hue) {
            if (!is_int($hue)) {
                throw new InvalidConfigurationException('Hues must be integers. Got ' . $hue . ' which is not an integer.');
            }

            $hueValue = intval($hue);

            if ($hueValue < 0 || $hueValue >= 360) {
                throw new InvalidConfigurationException('Hue values must be >= 0 and < 360. Got ' . $hueValue);
            }

            if (!is_array($config[$hue])) {
                throw new InvalidConfigurationException('Hue config entries must be arrays. The entry for hue ' . $hue . ' is not.');
            }

            $names = array_unique(array_keys($config[$hue]));
            if ($namedConfigs == null) {
                $namedConfigs = $names;
            } else {
                $dif = difference($names, $namedConfigs);
                if (count($dif) > 0) {
                    throw new InvalidConfigurationException('Config for hue does not have consistant names.');
                }
            }

            $hueConfig = array();
            foreach ($names as $name) {
                $hueConfig[$name] = array();
                foreach (array(self::KEY_SAT, self::KEY_VAL) as $component) {
                    if(!array_key_exists($component, $config[$hue][$name])) {
                        throw new InvalidConfigurationException('Missing ' . $component . ' component of ' . $hue . ':' . $name . '.');
                    }

                    $compValue = $config[$hue][$name][$component];
                    if (!is_numeric($compValue)) {
                        throw new InvalidConfigurationException('Value of ' . $component . ' must be a number. ' . $hue . ':' . $name . ':' . $component . ' is ' . $compValue . '.');
                    }

                    $compValue = floatval($compValue);

                    if ($compValue < 0 || $compValue > 100) {
                        throw new InvalidConfigurationException(
                            'Value of ' . $component . ' must be in the range of 0 to 100. ' . $hue . ':' . $name . ':' . $component . ' is ' . $compValue . '.'
                        );
                    }

                    $hueConfig[$name][$component] = $compValue;
                }
            }

            $this->_namedConfigs = $namedConfigs;
            $huePointSet[] = $hueValue;
            $this->_config[$hueValue] = $hueConfig;
        }

        $hueMin = min($huePointSet);
        $hueMax = max($huePointSet);

        $newMin = $hueMax - 360;
        $huePointSet[] = $newMin;
        $this->_config[$newMin] = $this->_config[$hueMax];

        $newMax = $hueMin + 360;
        $huePointSet[] = $newMax;
        $this->_config[$newMax] = $this->_config[$hueMin];

        $huePointSet = array_unique($huePointSet);
        if (!sort($huePointSet, SORT_NUMERIC)) {
            throw new InvalidConfigurationException('Failed to sort the hue points in the configuration');
        }
        $this->_huePoints = $huePointSet;
    }
}
