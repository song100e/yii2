<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Yii;
use Closure;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * ServiceLocator implements a [service locator](http://en.wikipedia.org/wiki/Service_locator_pattern).
 *
 * To use ServiceLocator, you first need to register component IDs with the corresponding component
 * definitions with the locator by calling [[set()]] or [[setComponents()]].
 * You can then call [[get()]] to retrieve a component with the specified ID. The locator will automatically
 * instantiate and configure the component according to the definition.
 *
 * For example,
 *
 * ```php
 * $locator = new \yii\di\ServiceLocator;
 * $locator->setComponents([
 *     'db' => [
 *         'class' => 'yii\db\Connection',
 *         'dsn' => 'sqlite:path/to/file.db',
 *     ],
 *     'cache' => [
 *         'class' => 'yii\caching\DbCache',
 *         'db' => 'db',
 *     ],
 * ]);
 *
 * $db = $locator->get('db');  // or $locator->db
 * $cache = $locator->get('cache');  // or $locator->cache
 * ```
 *
 * Because [[\yii\base\Module]] extends from ServiceLocator, modules and the application are all service locators.
 *
 * @property array $components The list of the component definitions or the loaded component instances (ID =>
 * definition or instance).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ServiceLocator extends Component
{
    /**
     * @var array shared component instances indexed by their IDs
     */
    private $_components = [];  // 用于缓存服务、组件等的实例
    /**
     * @var array component definitions indexed by their IDs
     */
    private $_definitions = []; // 用于保存服务和组件的定义，通常为配置数组，可以用来创建具体的实例


    /**
     * Getter magic method.
     * This method is overridden to support accessing components like reading properties.
     * @param string $name component or property name
     * @return mixed the named property value
     * 重载了 getter 方法，使得访问服务和组件就跟访问类的属性一样。同时，也保留了原来Component的 getter所具有的功能。
     * 请留意，ServiceLocator 并未重载 __set()，仍然使用 yii\base\Component::__set()
     */
    public function __get($name)
    {
        // has() 方法就是判断 $_definitions 数组中是否已经保存了服务或组件的定义
        // 请留意，这个时候服务或组件仅是完成定义，不一定已经实例化
        if ($this->has($name)) {
            return $this->get($name);   // get() 方法用于返回服务或组件的实例
        } else {
            return parent::__get($name);// 未定义的服务或组件，那么视为正常的属性、行为，
        }
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named component is loaded.
     * @param string $name the property name or the event name
     * @return boolean whether the property value is null
     * 对比Component，增加了对是否具有某个服务和组件的判断。
     */
    public function __isset($name)
    {
        if ($this->has($name, true)) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    /**
     * Returns a value indicating whether the locator has the specified component definition or has instantiated the component.
     * This method may return different results depending on the value of `$checkInstance`.
     *
     * - If `$checkInstance` is false (default), the method will return a value indicating whether the locator has the specified
     *   component definition.
     * - If `$checkInstance` is true, the method will return a value indicating whether the locator has
     *   instantiated the specified component.
     *
     * @param string $id component ID (e.g. `db`).
     * @param boolean $checkInstance whether the method should check if the component is shared and instantiated.
     * @return boolean whether the locator has the specified component definition or has instantiated the component.
     * @see set()
     */
    public function has($id, $checkInstance = false)
    {
        // 当 $checkInstance === true 时，用于判断是否已经有了某个服务或组件的实例。
        // 当 $checkInstance === false 时，用于判断是否已经定义了某个服务或组件。
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

    /**
     * Returns the component instance with the specified ID.
     *
     * @param string $id component ID (e.g. `db`).
     * @param boolean $throwException whether to throw an exception if `$id` is not registered with the locator before.
     * @return object|null the component of the specified ID. If `$throwException` is false and `$id`
     * is not registered before, null will be returned.
     * @throws InvalidConfigException if `$id` refers to a nonexistent component ID
     * @see has()
     * @see set()
     * 根据 $id 获取对应的服务或组件的实例
     */
    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];         // 如果已经有实例化好的组件或服务，直接使用缓存中的就OK了
        }

        if (isset($this->_definitions[$id])) {      // 如果还没有实例化好，那么再看看是不是已经定义好
            $definition = $this->_definitions[$id];
            // 如果定义是个对象，且不是Closure对象，那么直接将这个对象返回
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_components[$id] = $definition;
            } else {
                // 是个数组或者PHP callable，调用 Yii::createObject()来创建一个实例,实例化后，保存进 $_components 数组中，以后就可以直接引用了
                return $this->_components[$id] = Yii::createObject($definition);
            }
        } elseif ($throwException) {
            throw new InvalidConfigException("Unknown component ID: $id");
        } else {//即没实例化，也没定义，万能的Yii也没办法通过一个任意的ID，就给你找到想要的组件或服务呀，给你个 null 吧。
            return null;//表示Service Locator中没有这个ID的服务或组件。
        }
    }

    /**
     * Registers a component definition with this locator.
     *
     * For example,
     *
     * ```php
     * // a class name
     * $locator->set('cache', 'yii\caching\FileCache');
     *
     * // a configuration array
     * $locator->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // an anonymous function
     * $locator->set('cache', function ($params) {
     *     return new \yii\caching\FileCache;
     * });
     *
     * // an instance
     * $locator->set('cache', new \yii\caching\FileCache);
     * ```
     *
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * @param string $id component ID (e.g. `db`).
     * @param mixed $definition the component definition to be registered with this locator.
     * It can be one of the following:
     *
     * - a class name
     * - a configuration array: the array contains name-value pairs that will be used to
     *   initialize the property values of the newly created object when [[get()]] is called.
     *   The `class` element is required and stands for the the class of the object to be created.
     * - a PHP callable: either an anonymous function or an array representing a class method (e.g. `['Foo', 'bar']`).
     *   The callable will be called by [[get()]] to return an object associated with the specified component ID.
     * - an object: When [[get()]] is called, this object will be returned.
     *
     * @throws InvalidConfigException if the definition is an invalid configuration array
     * 用于注册一个组件或服务，其中 $id 用于标识服务或组件。$definition 可以是一个类名，一个配置数组，一个PHP callable，或者一个对象。
     */
    public function set($id, $definition)
    {
        if ($definition === null) {     // 当定义为 null 时，表示要从Service Locator中删除一个服务或组件
            unset($this->_components[$id], $this->_definitions[$id]);
            return;
        }

        unset($this->_components[$id]);// 确保服务或组件ID的唯一性
        // 定义如果是个对象或PHP callable，或类名，直接作为定义保存，留意这里 is_callable的第二个参数为true，所以，类名也可以。
        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_definitions[$id] = $definition;     // 定义的过程，只是写入了 $_definitions 数组
        } elseif (is_array($definition)) {
            // a configuration array
            if (isset($definition['class'])) {          // 定义如果是个数组，要确保数组中具有 class 元素
                $this->_definitions[$id] = $definition; // 定义的过程，只是写入了 $_definitions 数组
            } else {
                throw new InvalidConfigException("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {// 这也不是，那也不是，那么就抛出异常吧
            throw new InvalidConfigException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }

    /**
     * Removes the component from the locator.
     * @param string $id the component ID
     * 删除一个服务或组件
     */
    public function clear($id)
    {
        unset($this->_definitions[$id], $this->_components[$id]);
    }

    /**
     * Returns the list of the component definitions or the loaded component instances.
     * @param boolean $returnDefinitions whether to return component definitions instead of the loaded component instances.
     * @return array the list of the component definitions or the loaded component instances (ID => definition or instance).
     * 用于返回Service Locator的 $_components 数组或 $_definitions 数组，同时也是 components 属性的getter函数。
     */
    public function getComponents($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_definitions : $this->_components;
    }

    /**
     * Registers a set of component definitions in this locator.
     *
     * This is the bulk version of [[set()]]. The parameter should be an array
     * whose keys are component IDs and values the corresponding component definitions.
     *
     * For more details on how to specify component IDs and definitions, please refer to [[set()]].
     *
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * The following is an example for registering two component definitions:
     *
     * ```php
     * [
     *     'db' => [
     *         'class' => 'yii\db\Connection',
     *         'dsn' => 'sqlite:path/to/file.db',
     *     ],
     *     'cache' => [
     *         'class' => 'yii\caching\DbCache',
     *         'db' => 'db',
     *     ],
     * ]
     * ```
     *
     * @param array $components component definitions or instances
     * 批量方式注册服务或组件，同时也是 components 属性的setter函数
     */
    public function setComponents($components)
    {
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }
}
