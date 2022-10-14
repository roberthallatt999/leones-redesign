<?php
namespace detour_pro;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'detour_pro/helpers/eeharbor_abstracted.php';

/**
 * EEHarbor helper class
 *
 * Bridges the functionality gaps between EE versions.
 *
 * @package			eeharbor_helper
 * @version			1.4.3
 * @author			Tom Jaeger <Tom@EEHarbor.com>
 * @link			https://eeharbor.com
 * @copyright		Copyright (c) 2016, Tom Jaeger/EEHarbor
 */

// --------------------------------------------------------------------

class EEHelper extends \detour_pro\EEHarbor_abstracted {

	private $_module;
	private $_module_name;
	private $_ee_major_version;
	private $app_settings;

	public function __construct($info)
	{
		$this->_module = $info['module'];
		$this->_module_name = $info['module_name'];
	}

	public function instantiate($which) {
		ee()->api->instantiate($which);
	}

	public function getBaseURL($method='', $extra='')
	{
		if($method == '/') $method = '';
		elseif($method) $method = AMP.'method='.$method;

		$url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->_module.$method.$extra;

		if (version_compare(APP_VER, '2.6.0', '>=') && version_compare(APP_VER, '2.7.3', '=<')) {
			// Yay, workaround for EE 2.6.0 session bug
			$config_type = 'admin_session_type';
		} else {
			$config_type = 'cp_session_type';
		}

		$s = 0;
		switch (ee()->config->item($config_type)){
			case 's':
				$s = ee()->session->userdata('session_id', 0);
				break;
			case 'cs':
				$s = ee()->session->userdata('fingerprint', 0);
				break;
		}

		// Test if our URL already has the session and directive.
		parse_str(parse_url(str_replace('&amp;', '&', $url), PHP_URL_QUERY), $url_test);

		if(!empty($s) && (!isset($url_test['S']) || empty($url_test['S']))) $url .= AMP.'S='.$s;
		if(!isset($url_test['D']) || empty($url_test['D'])) $url .= AMP.'D=cp';

		return $url;
	}

	public function getNav($nav_items=array(), $buttons=array())
	{
		foreach($nav_items as $title => $method) {
			if(strpos($method, 'http') === false) $method = $this->getBaseURL($method);

			$nav_items[$title] = $method;
		}

		ee()->cp->set_right_nav($nav_items);
	}

	public function cpURL($path, $mode='', $variables=array())
	{
		switch($path) {
			case 'listing':
				$path = 'content_edit';
				$mode = '';
				if(isset($variables['filter_by_channel'])) {
					$variables['channel_id'] = $variables['filter_by_channel'];
					unset($variables['filter_by_channel']);
				}
				break;

			case 'publish':
				$path = 'content_publish';
				if($mode == 'create' || $mode == 'edit') $mode = 'entry_form';
				break;

			case 'members':
				if($mode == 'groups') $mode = 'member_group_manager';
				break;

			case 'channels':
				$path = 'admin_content';
				if($mode == 'create') $mode = 'channel_add';
				break;

			// case 'addons':
			// 	$path = 'addon_modules';
			// 	break;
		}

		$url = BASE.AMP.'D=cp'.AMP.'C='.$path;

		if($mode) $url .= AMP.'M='.$mode;

		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}

		return $url;
	}

	public function moduleURL($method='index', $variables=array())
	{
		$url = $this->getBaseURL() . AMP . 'method=' . $method;

		if(is_null($variables)) {
			$variables = array();
		}

		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}

		return $url;
	}

	public function view($view, $vars = array(), $return = FALSE)
	{
		return ee()->load->view($view, $vars, $return);
	}

	public function getCurrentPage($options = array())
	{
		// If we have the per_page query variable, it's an offset.
		if(ee()->input->get('per_page', 1)) {
			$offset = (int) ee()->input->get('per_page', 1);
			return ($offset / $options['per_page']) + 1;
		} elseif(ee()->input->get('page', 1)) {
			return (int) ee()->input->get('page', 1);
		} else {
			return 1;
		}
	}

	public function getStartNum($options)
	{
		return ($options['current_page'] * $options['per_page']) - $options['per_page'];
	}

	public function pagination($options = array())
	{
		// Remap from normal logic to EE2 logic.
		if(isset($options['current_page'])) $options['cur_page'] = $options['current_page'];

		ee()->load->library('pagination');
		ee()->pagination->initialize($options);
		return ee()->pagination->create_links();
	}

	public function getSettings($asArray = false)
	{
		if(! $this->settings_initiated())
		{
			$this->initiateSettings();
		}

		if($asArray) return $this->app_settings;

		return (object) $this->app_settings;

	}

	public function getConfig($item)
	{
		if(! $this->settings_initiated())
		{
			$this->initiateSettings();
		}

		return $this->app_settings[$item];
	}

	public function setConfig($item, $value)
	{
		// EE caches the list of DB tables, so unset the table_names var if it's set
		// otherwise table_exists could return a false negative if it was just created.
		if(isset(ee()->db->data_cache['table_names'])) unset(ee()->db->data_cache['table_names']);

		// Make sure the settings table exists.
		if(ee()->db->table_exists($this->_module.'_settings')) {
			// Find out if the settings exist, if not, insert them.
			ee()->db->where('site_id', ee()->config->item('site_id'));
			$exists = ee()->db->count_all_results($this->_module.'_settings');

			$data['site_id'] = ee()->config->item('site_id');
			$data[$item] = $value;

			if($exists) {
				ee()->db->where('site_id', ee()->config->item('site_id'));
				ee()->db->update($this->_module.'_settings', $data);
			} else {
				ee()->db->insert($this->_module.'_settings', $data);
			}
		}

		// Set variables
		$this->app_settings[$item] = $value;
		ee()->session->set_cache($this->_module, "settings", $this->app_settings);
	}

	public function cache($mode, $key = false, $data = false, $persistent = true) {
		if (! isset(ee()->session->cache[$this->_module]))
		{
			ee()->session->cache[$this->_module] = array();
		}

		// Returns EE's native cache function for EE2.
		switch($mode) {
			case 'get':
				if($persistent && version_compare(APP_VER, '2.8.0', '>=')) return ee()->cache->get('/'.$this->_module.'/'.$key);
				elseif(isset(ee()->session->cache[$this->_module][$key])) return ee()->session->cache[$this->_module][$key];
				else return false;
				break;

			case 'set':
				if($persistent && version_compare(APP_VER, '2.8.0', '>=')) return ee()->cache->save('/'.$this->_module.'/'.$key, $data);
				else return ee()->session->cache[$this->_module][$key] = $data;
				break;

			case 'delete':
			case 'clear':
				if($key) unset(ee()->session->cache[$this->_module][$key]);
				else unset(ee()->session->cache[$this->_module]);
				break;

			default:
				return false;
		}
	}

	/**
	 * Flash a message to the screen
	 * @param  string $type             Type of message to display. [message_success, message_notice, message_error, message_failure]
	 * @param  string $title            Title of flash message (Concatenated with body when EE2)
	 * @param  string $body             Title of flash message (Concatenated with title when EE2)
	 * @param  array  $extra_parameters Name of EE3 alert functions to call in addition to the default ones. (does nothing in EE2) ex. ['cannotClose']
	 */
	public function flashData($type='message_success', $title='', $body='', $extra_parameters=array()) {
		ee()->session->set_flashdata($type, $title . " " . $body);
	}

	/**
	 * Gets the directory for the addon's theme files
	 * @return [string] [path of directory]
	 */
	public function getAddonThemesDir() {
		return "/themes/third_party/" . $this->_module . '/';
	}

	/**
	 * Overwrite any native EE Classes.
	 * EE2 uses direct assignment.
	 *
	 * @param object $class    The EE class object you want to overwrite
	 * @param object $data     The optional data used to overwrite.
	 **/
	public function overwriteEEClass($class, $data='') {
		ee()->{$class} = $data;
	}

	/**
	 * Remove any native EE Classes.
	 * EE2 uses direct assignment.
	 *
	 * @param object $class    The EE class object you want to overwrite
	 **/
	public function removeEEClass($class) {
		ee()->{$class} = '';
	}

	/**
	 * XSS protection for user input
	 * @param  String or Array $input xss_clean accepts a string or array as input.
	 * @return Sanitized string or array
	 */
	public function xss_clean($input)
	{
		return ee()->security->xss_clean($input);
	}

	/**
	 * Get information about the current page (in the CP)
	 * @param  [string] $options option to only get a portion of the information rather than an array
	 * @return [string or array]         full path info in array, or single element
	 */
	public function getCurrentUrlInfo($options = null)
	{
		$url = @trim($_SERVER['QUERY_STRING'], "/");
		$segments = @explode( "/", $url);
		$url_info['full'] = $url;
		$url_info['cp'] = (@$segments[0] === "cp");
		$url_info['segments'] = $segments;
		$url_info['module'] = @$_GET["module"];
		$url_info['method'] = array_key_exists("method", $_GET) ? @$_GET["method"] : "index";

		if($options && array_key_exists($options, $url_info))
			return $url_info[$options];

		return $url_info;
	}

	/**
	 * Returns the system cache path
	 * @return string - path to cache
	 */
	public function getCachePath() {
		$cache_path = ee()->config->item('cache_path');

		if (empty($cache_path))
			$cache_path = APPPATH.'cache/';

		return $cache_path;
	}

	/**
	 * Provides a quick boolean for checking ee version
	 * @return boolean is_ee2
	 */
	public function is_ee2() {
		return true;
	}

	/**
	 * Provides a quick boolean for checking ee version
	 * @return boolean is_ee3
	 */
	public function is_ee3() {
		return false;
	}

	/**
	 * Provides a quick boolean for checking ee version
	 * @return boolean is_ee4
	 */
	public function is_ee4() {
		return false;
	}

	/**
	 * Call the EE method for removing double slashes. Is specific to the EE version.
	 * @return string result
	 */
	public function reduce_double_slashes($string) {
		if(version_compare(APP_VER, '2.6.0', '<')) {
			return ee()->functions->remove_double_slashes($string);
		} else {
			ee()->load->helper('string');
			return reduce_double_slashes($string);
		}
	}

	/**
	 * Initiate the settings if they are not already initiated
	 * @return [Boolean] [description]
	 */
	private function initiateSettings()
	{
		// Early return if the settings have already been initiated
		if($this->settings_initiated())
		{
			return true;
		}

		// EE caches the list of DB tables, so unset the table_names var if it's set
		// otherwise table_exists could return a false negative if it was just created.
		if(isset(ee()->db->data_cache['table_names'])) unset(ee()->db->data_cache['table_names']);

		$dbSettings = array();

		if(ee()->db->table_exists($this->_module.'_settings'))
		{
			$dbSettingsQuery = ee()->db->get_where($this->_module.'_settings', array($this->_module.'_settings.site_id' => ee()->config->item('site_id')));

			if($this->_module === "structure")
			{
				foreach($dbSettingsQuery->result() as $row)
				{
					$dbSettings[$row->var] = $row->var_value;
				}
			} else {
				$dbSettings = $dbSettingsQuery->row_array();
			}
		}

		// Fieldtype settings
		$ftSettingsQuery = ee()->db->select('settings')
				->where('name', $this->_module)
				->get('fieldtypes');

		if((bool)$ftSettingsQuery->num_rows())
		{
			$ftSettings = @unserialize(@base64_decode($ftSettingsQuery->row('settings')));

			// It is possible there is actually nothing in the FT settings. So if not, just return an empty array
			if(!$ftSettings)
				$ftSettings = array();

		} else {
			$ftSettings = array();
		}

		$addonSettings = require PATH_THIRD.'detour_pro/addon.setup.php';

		$this->app_settings = array_merge(array_merge($ftSettings, $dbSettings), $addonSettings);

		// cache the settings as an array
		if(isset(ee()->session) && method_exists(ee()->session, 'set_cache')) {
			ee()->session->set_cache($this->_module, "settings", $this->app_settings);
		}

		return true;
	}

	/**
	 * Check if the settings have been initiated
	 * @return [bool] True/False for initiated settings
	 */
	private function settings_initiated()
	{
		// Settings are initiated if this variable is set
		if(isset($this->app_settings))
		{
			return true;
		}

		if(isset(ee()->session) && method_exists(ee()->session, 'cache') && ee()->session->cache($this->_module, "settings", false))
		{
			// $this->app_settings were not set before apparently, so lets set them to the cached version
			$this->app_settings = ee()->session->cache($this->_module, "settings", false);

			return true;
		}

		return false;
	}

	/**
	 * Inject Javascript on the page
	 */
	public function javascript_to_page($js)
	{
		ee()->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
	}

	/**
	 * Inject EEHarbor ping to page
	 */
	public function version_check($type = "php")
	{
		if($type==="js" OR $type==="javascript")
		{
			$this->version_check_js();
		} else {
			return $this->version_check_php();
		}
	}

	/**
	 * Inject EEHarbor ping to page
	 */
	public function version_check_js()
	{
		$license = $this->getLicenseKey();
		$addon = "detour_pro";
		$version = $this->getConfig('version');
		$ee_version = $this->getEEVersion(false);
		$domain = ee()->config->config['base_url'];

		$js = "
		var post_data = {
			license: '$license',
			addon: '$addon',
			version: '$version',
			ee: '$ee_version',
			domain: '$domain'
		};

		$.ajax({
			type: 'POST',
			url: 'http://ping.eeharbor.com',
			data: post_data,
			success: function (data) {
				var parsed_data = JSON.parse(data);
				// console.log(parsed_data);
			}
		});";

		$this->javascript_to_page($js);
	}

	public function version_check_php()
	{
		// Attempt to grab the local cached file
		$cached = ee()->cache->get($this->_module."_version", \Cache::GLOBAL_SCOPE);

		// Return, since the version has already been checked.
		if($cached)
			return $cached;

		$target = parse_url("http://ping.eeharbor.com/");

		$fp = @fsockopen($target['host'], 80, $errno, $errstr, 3);

		if ( ! $fp)
			return false;

		$payload = array(
				'license' => $this->getLicenseKey(),
				'addon' => "detour_pro",
				'version' => $this->getConfig('version'),
				'ee' => $this->getEEVersion(false),
				'domain' => ee()->config->config['base_url']
			);

		$postdata = http_build_query($payload);

		fputs($fp, "POST {$target['path']} HTTP/1.0\r\n");
		fputs($fp, "Host: {$target['host']}\r\n");
		fputs($fp, "User-Agent: Add-on Version PHP/\r\n");
		fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-Length: ".strlen($postdata)."\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, "{$postdata}\r\n\r\n");

		$headers = true;
		$response = '';
		while ( ! feof($fp))
		{
			$line = fgets($fp, 4096);

			if ($headers === false)
			{
				$response .= $line;
			}
			elseif (trim($line) == '')
			{
				$headers = false;
			}
		}
		fclose($fp);

		$current_info = json_decode($response);

		// Cache version information for a day
		ee()->cache->save(
			$this->_module."_version",
			$current_info,
			60 * 60 * 24,
			\Cache::GLOBAL_SCOPE
		);

		return $current_info;
	}

	public function getLicenseKey()
	{
		return @$this->getConfig('license') ?: @$this->getConfig('license_key') ?: '';
	}

	public function module_installed($module)
	{
		$exists = ee()->db->where('module_name', $module)->count_all_results('modules');

        if($exists) return true;
        else return false;
	}
}
