<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Request represents a request that is handled by an [[Application]].
 *
 * @property boolean $isConsoleRequest The value indicating whether the current request is made via console.
 * @property string $scriptFile Entry script file path (processed w/ realpath()).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class Request extends Component
{
    private $_scriptFile;       // 属性scriptFile，用于表示入口脚本
    private $_isConsoleRequest; // 属性isConsoleRequest，用于表示是否是命令行应用


    /**
     * Resolves the current request into a route and the associated parameters.
     * @return array the first element is the route, and the second is the associated parameters.
     * 虚函数，要求子类来实现,这个函数的功能主要是为了把Request解析成路由和相应的参数
     */
    abstract public function resolve();

    /**
     * Returns a value indicating whether the current request is made via command line
     * @return boolean the value indicating whether the current request is made via console
     * isConsoleRequest属性的getter函数,使用 PHP_SAPI 常量判断当前应用是否是命令行应用
     */
    public function getIsConsoleRequest()
    {
        // 一切 PHP_SAPI 不为 'cli' 的，都不是命令行
        return $this->_isConsoleRequest !== null ? $this->_isConsoleRequest : PHP_SAPI === 'cli';
    }

    /**
     * Sets the value indicating whether the current request is made via command line
     * @param boolean $value the value indicating whether the current request is made via command line
     */
    public function setIsConsoleRequest($value)
    {
        $this->_isConsoleRequest = $value;  // isConsoleRequest属性的setter函数
    }

    /**
     * Returns entry script file path.
     * @return string entry script file path (processed w/ realpath())
     * @throws InvalidConfigException if the entry script file path cannot be determined automatically.
     * scriptFile属性的getter函数,通过 $_SERVER['SCRIPT_FILENAME'] 来获取入口脚本名
     */
    public function getScriptFile()
    {
        if ($this->_scriptFile === null) {
            if (isset($_SERVER['SCRIPT_FILENAME'])) {
                $this->setScriptFile($_SERVER['SCRIPT_FILENAME']);
            } else {
                throw new InvalidConfigException('Unable to determine the entry script file path.');
            }
        }

        return $this->_scriptFile;
    }

    /**
     * Sets the entry script file path.
     * The entry script file path can normally be determined based on the `SCRIPT_FILENAME` SERVER variable.
     * However, for some server configurations, this may not be correct or feasible.
     * This setter is provided so that the entry script file path can be manually specified.
     * @param string $value the entry script file path. This can be either a file path or a path alias.
     * @throws InvalidConfigException if the provided entry script file path is invalid.
     * scriptFile属性的setter函数
     */
    public function setScriptFile($value)
    {
        $scriptFile = realpath(Yii::getAlias($value));
        if ($scriptFile !== false && is_file($scriptFile)) {
            $this->_scriptFile = $scriptFile;
        } else {
            throw new InvalidConfigException('Unable to determine the entry script file path.');
        }
    }
}
