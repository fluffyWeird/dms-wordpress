<?php

/**
 * Class WptmDivi
 */
class WptmDivi extends DiviExtension
{
    /**
     * The gettext domain for the extension's translations.
     *
     * @var string
     */
    public $gettext_domain = 'divi-divi_wptm';

    /**
     * The extension's WP Plugin name.
     *
     * @var string
     */
    public $name = 'divi_wptm';

    /**
     * The extension's version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * DIVI_DiviWptm constructor.
     *
     * @param string $name Name extension
     * @param array  $args Parameter
     *
     * @return void
     */
    public function __construct($name = 'divi_wptm', $args = array())
    {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_dir_url = plugin_dir_url($this->plugin_dir);

        parent::__construct($name, $args);
    }
}

new WptmDivi;
