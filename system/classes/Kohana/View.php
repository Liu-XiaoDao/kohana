<?php
/**
 * Acts as an object wrapper for HTML pages with embedded PHP, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_View {

	/**
	 * @param mixed[] Array of global variables
	 * @deprecated
	 */
	protected static $_global_data = array();//全局数据

	/**
	 * Returns a new View object. If you do not define the "file" parameter,
	 * you must call [View::set_filename].
	 *
	 *     $view = View::factory($file);
	 *
	 * @param   string  $file   view filename
	 * @param   array   $data   array of values
	 * @return  View
	 */
	public static function factory($file = NULL, array $data = NULL)//工厂方法
	{
		return new View($file, $data);
	}

	/**
	 * Captures the output that is generated when a view is included.
	 * The view data will be extracted to make local variables. This method
	 * is static to prevent object scope resolution.
	 *
	 *     $output = View::capture($file, $data);
	 *
	 * @param   string  $kohana_view_filename   filename
	 * @param   array   $kohana_view_data       variables
	 * @return  string
	 * @throws  Exception
	 */

	protected static function capture($kohana_view_filename, array $kohana_view_data)//缓冲
	{
		// Import the view variables to local namespace
		extract($kohana_view_data, EXTR_SKIP);//将数组中的变量,拿出来放到当前空间

		if (View::$_global_data)
		{
			// Import the global view variables to local namespace
			extract(View::$_global_data, EXTR_SKIP | EXTR_REFS);//这个也拿出来
		}

		// Capture the view output
		ob_start();//开启输出缓冲

		try
		{
			// Load the view within the current scope
			include $kohana_view_filename;
		}
		catch (Exception $e)
		{
			// Delete the output buffer
			ob_end_clean();//清空缓冲区内容但是不输出

			// Re-throw the exception
			throw $e;
		}

		// Get the captured output and close the buffer
		return ob_get_clean();//返回缓冲区长度
	}

	/**
	 * Sets a global variable, similar to [View::set], except that the
	 * variable will be accessible to all views.
	 *
	 *     View::set_global($name, $value);
	 * 
	 * [!!] Use of global view variables is deprecated and strongly discouraged
	 *
	 * You can also use an array or Traversable object to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the view
	 *     View::set_global(array('food' => 'bread', 'beverage' => 'water'));
	 *
	 * [!!] Note: When setting with using Traversable object we're not attaching the whole object to the view,
	 * i.e. the object's standard properties will not be available in the view context.
	 *
	 * @deprecated in favour of setting relevant variables on each specific view
	 * @param   string|array|Traversable  $key    variable name or an array of variables
	 * @param   mixed                     $value  value
	 * @return  void
	 */
	public static function set_global($key, $value = NULL)//数据放到全局数据
	{
		if (is_array($key) OR $key instanceof Traversable)
		{
			foreach ($key as $name => $value)
			{
				View::$_global_data[$name] = $value;
			}
		}
		else
		{
			View::$_global_data[$key] = $value;
		}
	}

	/**
	 * Assigns a global variable by reference, similar to [View::bind], except
	 * that the variable will be accessible to all views.
	 *
	 *     View::bind_global($key, $value);
	 *
	 * [!!] Use of global view variables is deprecated and strongly discouraged
	 *
	 * @deprecated in favour of setting relevant variables on each specific view
	 * @param   string  $key    variable name
	 * @param   mixed   $value  referenced variable
	 * @return  void
	 */
	public static function bind_global($key, & $value)//绑定到全局数据
	{
		View::$_global_data[$key] =& $value;
	}

	// View filename
	protected $_file;

	// Array of local variables
	protected $_data = array();

	/**
	 * Sets the initial view filename and local data. Views should almost
	 * always only be created using [View::factory].
	 *
	 *     $view = new View($file);
	 *
	 * @param   string  $file   view filename
	 * @param   array   $data   array of values
	 * @return  void
	 * @uses    View::set_filename
	 */
	public function __construct($file = NULL, array $data = NULL)//文件名和数据
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if ($data !== NULL)
		{
			// Add the values to the current data
			$this->_data = $data + $this->_data;
		}
	}

	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $view->foo;
	 *
	 * [!!] If the variable has not yet been set, an exception will be thrown.
	 *
	 * @param   string  $key    variable name
	 * @return  mixed
	 * @throws  Kohana_Exception
	 */
	public function & __get($key)//返回一个数据
	{
		if (array_key_exists($key, $this->_data))
		{
			return $this->_data[$key];
		}
		elseif (array_key_exists($key, View::$_global_data))
		{
			return View::$_global_data[$key];
		}
		else
		{
			throw new Kohana_Exception('View variable is not set: :var',
				array(':var' => $key));
		}
	}

	/**
	 * Magic method, calls [View::set] with the same parameters.
	 *
	 *     $view->foo = 'something';
	 *
	 * @param   string  $key    variable name
	 * @param   mixed   $value  value
	 * @return  void
	 */
	public function __set($key, $value)//设置数据
	{
		$this->set($key, $value);
	}

	/**
	 * Magic method, determines if a variable is set.
	 *
	 *     isset($view->foo);
	 *
	 * [!!] `NULL` variables are not considered to be set by [isset](http://php.net/isset).
	 *
	 * @param   string  $key    variable name
	 * @return  boolean
	 */
	public function __isset($key)//data里是否有这个值,全局数据里是否有
	{
		return (isset($this->_data[$key]) OR isset(View::$_global_data[$key]));
	}

	/**
	 * Magic method, unsets a given variable.
	 *
	 *     unset($view->foo);
	 *
	 * @param   string  $key    variable name
	 * @return  void
	 */
	public function __unset($key)//移除某个数据
	{
		unset($this->_data[$key], View::$_global_data[$key]);
	}

	/**
	 * Magic method, returns the output of [View::render].
	 *
	 * @return  string
	 * @uses    View::render
	 */
	public function __toString()//转成字符串
	{
		try
		{
			return $this->render();
		}
		catch (Exception $e)
		{
			/**
			 * Display the exception message.
			 *
			 * We use this method here because it's impossible to throw an
			 * exception from __toString().
			 */
			$error_response = Kohana_Exception::_handler($e);

			return $error_response->body();
		}
	}

	/**
	 * Sets the view filename.
	 *
	 *     $view->set_filename($file);
	 *
	 * @param   string  $file   view filename
	 * @return  View
	 * @throws  View_Exception
	 */
	public function set_filename($file)//把文件路径保存到对象的file属性
	{
		if (($path = Kohana::find_file('views', $file)) === FALSE)
		{
			throw new View_Exception('The requested view :file could not be found', array(
				':file' => $file,
			));
		}

		// Store the file path locally
		$this->_file = $path;

		return $this;
	}

	/**
	 * Assigns a variable by name. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This value can be accessed as $foo within the view
	 *     $view->set('foo', 'my value');
	 *
	 * You can also use an array or Traversable object to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the view
	 *     $view->set(array('food' => 'bread', 'beverage' => 'water'));
	 *
	 * [!!] Note: When setting with using Traversable object we're not attaching the whole object to the view,
	 * i.e. the object's standard properties will not be available in the view context.
	 *
	 * @param   string|array|Traversable  $key    variable name or an array of variables
	 * @param   mixed                     $value  value
	 * @return  $this
	 */
	public function set($key, $value = NULL)//存储数据
	{
		if (is_array($key) OR $key instanceof Traversable)//检查是不是一个数组或者能不能遍历
		{
			foreach ($key as $name => $value)
			{
				$this->_data[$name] = $value;
			}
		}
		else
		{
			$this->_data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Assigns a value by reference. The benefit of binding is that values can
	 * be altered without re-setting them. It is also possible to bind variables
	 * before they have values. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This reference can be accessed as $ref within the view
	 *     $view->bind('ref', $bar);
	 *
	 * @param   string  $key    variable name
	 * @param   mixed   $value  referenced variable
	 * @return  $this
	 */
	public function bind($key, & $value)//设置一个数据
	{
		$this->_data[$key] =& $value;

		return $this;
	}

	/**
	 * Renders the view object to a string. Global and local data are merged
	 * and extracted to create local variables within the view file.
	 *
	 *     $output = $view->render();
	 *
	 * [!!] Global variables with the same key name as local variables will be
	 * overwritten by the local variable.
	 *
	 * @param   string  $file   view filename
	 * @return  string
	 * @throws  View_Exception
	 * @uses    View::capture
	 */
	public function render($file = NULL)//渲染
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if (empty($this->_file))
		{
			throw new View_Exception('You must set the file to use within your view before rendering');
		}

		// Combine local and global data and capture the output
		return View::capture($this->_file, $this->_data);
	}

}
