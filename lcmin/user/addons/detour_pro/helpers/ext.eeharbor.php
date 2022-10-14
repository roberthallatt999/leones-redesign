<?php
namespace detour_pro;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EEHarbor extension parent class
 *
 * @package			EEHarbor Extension Parent
 * @version			1.4.3
 * @author			Tom Jaeger <Tom@EEHarbor.com>
 * @link			https://eeharbor.com
 * @copyright		Copyright (c) 2016, Tom Jaeger/EEHarbor
 */

// --------------------------------------------------------------------

class Eeharbor_ext {

	public function __construct()
	{

	}

	/**
	 * Disable Extension
	 * @return void
	 */
	public function disable_extension()
	{
		ee()->db->where('class', get_class($this));
		ee()->db->delete('extensions');

		$eeharbor = new \detour_pro\EEHarbor;

		// If the user installed the add-on menu item, we need to remove it.
		// This is only available in EE3 so far. So dont try if it is EE2
		if(! $eeharbor->is_ee2())
		{
			ee()->db->where('data', get_class($this));
			ee()->db->delete('menu_items');
		}
	}


	protected function update_version()
	{
		ee()->db->update(
				'extensions',
				array(
					'version' => $this->version
				),
				array(
					'class'  => get_class($this),
				)
			);
	}

	protected function register_extension($method, $hook=null, $priority=10, $enabled = 'y')
	{
		// if hook is empty, it should really just be the same thing as $method
		if(!$hook) {
			$hook = $method;
		}

		if(! isset($this->settings)) {
			$this->settings = array();
		}

		// We are searching the database for this extension, and determining if it already exists
		$already_exists = (bool) ee()->db->get_where('extensions', array(
					'class'     => get_class($this),
					'method'    => $method,
					'hook'      => $hook))->num_rows;

		// if it already exists, lets not add another.
		if ($already_exists) return true;

		$data = array(
				'class'     => get_class($this),
				'method'    => $method,
				'hook'      => $hook,
				'settings'  => serialize($this->settings),
				'priority'  => $priority,
				'version'   => $this->version,
				'enabled'   => $enabled
			);

		ee()->db->insert('extensions', $data);
	}

	protected function unregister_extension($method, $hook=null)
	{
		// if hook is empty, it should really just be the same thing as $method
		if(!$hook) {
			$hook = $method;
		}

		// Remove the hook from the `exp_extensions` table. It doesn't matter if it doesn't exist.
		ee()->db->delete('extensions', array(
				'class'  => get_class($this),
				'method' => $method,
				'hook'   => $hook));

		return true;
	}

	/**************************************************\
	 ******************* ALL HOOKS: *******************
	\**************************************************/

	/**
	 * cp_custom_menu
	 */
	public function cp_custom_menu($menu)
	{
		// Do work only on control panel requests
		if (REQ != 'CP') {
			return true;
		}

		$eeharbor = new \detour_pro\EEHarbor;

		$menu->addItem($eeharbor->getConfig('name'), $eeharbor->moduleURL());
	}
}
