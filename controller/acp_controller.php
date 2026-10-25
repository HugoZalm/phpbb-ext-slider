<?php
/**
 *
 * Slider. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Hugo
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace hugozalm\slider\controller;

use hugozalm\slider\slides;

/**
 * Slider ACP controller: the default display options for Slider tags.
 */
class acp_controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string Custom form action */
	protected $u_action;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config		$config		Config object
	 * @param \phpbb\language\language	$language	Language object
	 * @param \phpbb\log\log			$log		Log object
	 * @param \phpbb\request\request	$request	Request object
	 * @param \phpbb\template\template	$template	Template object
	 * @param \phpbb\user				$user		User object
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, \phpbb\log\log $log, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->config	= $config;
		$this->language	= $language;
		$this->log		= $log;
		$this->request	= $request;
		$this->template	= $template;
		$this->user		= $user;
	}

	/**
	 * Display the options a user can configure for this extension.
	 *
	 * @return void
	 */
	public function display_options()
	{
		// Add the language file with the settings strings
		$this->language->add_lang('acp/slider', 'hugozalm/slider');

		// Create a form key for preventing CSRF attacks
		add_form_key('slider_acp');

		// Create an array to collect errors that will be output to the user
		$errors = [];

		$values = [
			'height'		=> (int) $this->config['slider_height'],
			'autoplay'		=> (int) $this->config['slider_autoplay'],
			'thumbnails'	=> (int) $this->config['slider_thumbnails'],
			'caption'		=> (string) $this->config['slider_caption'],
		];

		// Is the form being submitted to us?
		if ($this->request->is_set_post('submit'))
		{
			// Test if the submitted form is valid
			if (!check_form_key('slider_acp'))
			{
				$errors[] = $this->language->lang('FORM_INVALID');
			}

			// Read as text so an empty or garbled field is reported, not turned into 0.
			foreach (array_keys($values) as $name)
			{
				$values[$name] = $this->request->variable('slider_' . $name, '');
			}

			// The same limits apply as to the options in a Slider tag. Errors are
			// ACP_SLIDER_HEIGHT_INVALID, _AUTOPLAY_, _THUMBNAILS_ and _CAPTION_INVALID.
			foreach (slides::invalid_options($values) as $name)
			{
				$errors[] = $this->language->lang('ACP_SLIDER_' . strtoupper($name) . '_INVALID');
			}

			// If no errors, process the form data
			if (!count($errors))
			{
				// Store the values in normal form, e.g. 0400 as 400.
				foreach (slides::options($values, slides::DEFAULTS) as $name => $value)
				{
					$this->config->set('slider_' . $name, $value);
				}

				// Add option settings change action to the admin log
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_SLIDER_SETTINGS');

				// Option settings have been updated and logged.
				// Confirm this to the user and provide a link back to the previous page.
				trigger_error($this->language->lang('ACP_SLIDER_SETTING_SAVED') . adm_back_link($this->u_action));
			}
		}

		$s_errors = !empty($errors);

		// Set output variables for display in the template
		$this->template->assign_vars([
			'S_ERROR'		=> $s_errors,
			'ERROR_MSG'		=> $s_errors ? implode('<br>', $errors) : '',

			'U_ACTION'		=> $this->u_action,

			'SLIDER_HEIGHT'		=> $values['height'],
			'SLIDER_AUTOPLAY'	=> $values['autoplay'],
			'SLIDER_THUMBNAILS'	=> (int) $values['thumbnails'],
			'SLIDER_CAPTION'	=> $values['caption'],
		]);
	}

	/**
	 * Set custom form action.
	 *
	 * @param string	$u_action	Custom form action
	 * @return void
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
