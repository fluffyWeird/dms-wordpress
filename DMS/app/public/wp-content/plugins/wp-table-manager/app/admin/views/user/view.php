<?php
/**
 * WP table manager
 *
 * @package WP table manager
 * @author  Joomunited
 * @version 2.3
 */

use Joomunited\WPFramework\v1_0_6\View;
use Joomunited\WPFramework\v1_0_6\Utilities;

defined('ABSPATH') || die();

/**
 * Class wptmViewUser
 */
class WptmViewUser extends View
{
    /**
     * Render
     *
     * @param null $tpl Tpl
     *
     * @return void
     */
    public function render($tpl = null)
    {
        $this->check  = (int) Utilities::getInt('check');
        $this->idUser = get_current_user_id();
        parent::render($tpl);
    }
}
