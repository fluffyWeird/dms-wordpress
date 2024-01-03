<?php
/**
 * WP Table Manager
 *
 * @package WP Table Manager
 * @author  Joomunited
 * @version 1.0
 */

use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Factory;
use Joomunited\WPFramework\v1_0_6\Form;

defined('ABSPATH') || die();

/**
 * Class wptmViewConfig
 */
class WptmViewConfig extends View
{
    /**
     * Function render
     *
     * @param null $tpl Tpl
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $modelConf    = $this->getModel('config');
        $this->config = $modelConf->getConfig();
        $form         = new Form();
        if ($form->load('config', $this->config)) {
            $this->configform = $form->render();
        }
        parent::render($tpl);
    }
}
