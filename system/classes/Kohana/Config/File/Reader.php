<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * File-based configuration reader. Multiple configuration directories can be
 * used by attaching multiple instances of this class to [Kohana_Config].
 *
 * @package    Kohana
 * @category   Configuration
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Config_File_Reader implements Kohana_Config_Reader {//配置文件读取

	/**
	 * The directory where config files are located
	 * @var string
	 */
	protected $_directory = '';//保存目录

	/**
	 * Creates a new file reader using the given directory as a config source
	 *
	 * @param string    $directory  Configuration directory to search
	 */
	public function __construct($directory = 'config')
	{
		// Set the configuration directory name
		$this->_directory = trim($directory, '/');
	}

	/**
	 * Load and merge all of the configuration files in this group.
	 *
	 *     $config->load($name);
	 *
	 * @param   string  $group  configuration group name
	 * @return  $this   current object
	 * @uses    Kohana::load
	 */
	public function load($group)//加载
	{
		$config = array();//保存数组

		if ($files = Kohana::find_file($this->_directory, $group, NULL, TRUE))
		{

			foreach ($files as $file)
			{

				// Merge each file to the configuration array
				$config = Arr::merge($config, Kohana::load($file));

			}
		}

		return $config;
	}

}
