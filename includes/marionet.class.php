<?php

class Marionet
{
    private static
        $initiated = false,
        $user_id,
        $userFirstName,
        $userLastName,
        $userEmail,
        $userPhone,
        $userDateOfBirth,
        $userGender,
        $log = 0
    ;

    public static function init()
    {
        if(!self::$initiated )
        {
            self::init_hooks();
            self::$initiated = true;
            load_plugin_textdomain('marionet', false, 'marionet/languages');
        }
    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        //Calling a function add administrative menu.
        add_action( 'admin_menu', array('Marionet', 'plgn_add_pages') );

        if(!is_admin())
        {
            add_action('wp_head', array('Marionet', 'marionet_main') );
        }

        add_action( 'woocommerce_before_single_product', array('Marionet', 'productView'));
        add_action( 'woocommerce_cart_updated', array('Marionet', 'submitCart'));
        add_action( 'woocommerce_checkout_order_processed', array('Marionet', 'submitOrder'));
        add_action( 'woocommerce_order_status_changed', array('Marionet', 'stateOrder') );
        add_action( 'wp_trash_post', array('Marionet', 'deleteOrder') );

        register_uninstall_hook( __FILE__, array('Marionet', 'delete_options') );
    }

    // Function for delete options
    public static function delete_options()
    {
        delete_option('marionet_plgn_options');
    }

    public static function plgn_add_pages()
    {
        add_submenu_page(
            'plugins.php',
            __( 'Marionet', 'marionet' ),
            __( 'Marionet', 'marionet' ),
            'manage_options',
            "marionet",
            array('Marionet', 'plgn_settings_page')
        );
        //call register settings function
        add_action( 'admin_init', array('Marionet', 'plgn_settings') );
    }

    public static function plgn_options_default()
    {
        return array(
            'app_key' => '',
            'currency_excange_rate' => '1'
        );
    }

    public static function plgn_settings()
    {
        $plgn_options_default = self::plgn_options_default();

        if(!get_option('marionet_plgn_options'))
        {
            add_option('marionet_plgn_options', $plgn_options_default, '', 'yes');
        }

        $plgn_options = get_option('marionet_plgn_options');
        $plgn_options = array_merge($plgn_options_default, $plgn_options);

        update_option('marionet_plgn_options', $plgn_options);
    }

    //Function formed content of the plugin's admin page.
    public static function plgn_settings_page()
    {
        $marionet_plgn_options = self::get_params();
        $marionet_plgn_options_default = self::plgn_options_default();
        $message = "";
        $error = "";

        if(isset($_REQUEST['marionet_plgn_form_submit'])
            && check_admin_referer(plugin_basename(dirname(__DIR__)), 'marionet_plgn_nonce_name'))
        {
            foreach($marionet_plgn_options_default as $k => $v)
            {
                if($k == 'currency_excange_rate')
                {
                    $value = trim(self::request($k, $v));
                    $value = (float)str_replace(',', '.', $value);
                    $marionet_plgn_options[$k] = $value;
                }
                else
                {
                    $marionet_plgn_options[$k] = trim(self::request($k, $v));
                }

            }

            update_option('marionet_plgn_options', $marionet_plgn_options);

            $message = __("Settings saved", 'marionet');
        }

        $options = array(
            'marionet_plgn_options' => $marionet_plgn_options,
            'message' => $message,
            'error' => $error,
        );

        echo self::loadTPL('adminform', $options);
    }

    private static function loadTPL($name, $options)
    {
        $tmpl = ( CONVEAD_PLUGIN_DIR .'tmpl/' . $name . '.php');

        if(!is_file($tmpl))
            return __('Error Load Template', 'marionet');

        extract($options, EXTR_PREFIX_SAME, "marionet");

        ob_start();

        include $tmpl;

        return ob_get_clean();
    }

    private static function request($name, $default=null)
    {
        return (isset($_REQUEST[$name])) ? $_REQUEST[$name] : $default;
    }

    //На всех страницах
    public static function marionet_main()
    {
        $marionet_plgn_options = self::get_params();
        if(!empty($marionet_plgn_options['app_key']))
        {
            $marionetSettings = '';
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;

            if($user_id > 0)
            {
                self::updateUserInfo();

                $info = array();
                if(!empty(self::$userFirstName))        $info['firstName']      = self::$userFirstName;
                if(!empty(self::$userLastName))         $info['lastName']       = self::$userLastName;
                if(!empty(self::$userEmail))            $info['email']           = self::$userEmail;
                if(!empty(self::$userPhone))            $info['phone']           = self::$userPhone;
                if(!empty(self::$userDateOfBirth))      $info['dateOfBirth']   = self::$userDateOfBirth;
                if(!empty(self::$userGender))           $info['gender']          = self::$userGender;

                do_action( 'marionet_visitor_info', $info );

                $info['id'] = $user_id
            }
            ?>
<script type="text/javascript">
(function(w,d,c,h){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var t = d.createElement('script');t.charset = 'utf-8';t.async = true;t.type = 'text/javascript';t.src = h;var s = d.getElementsByTagName('script')[0];s.parentNode.insertBefore(t, s);})(window,document,'marionet','https://cdn.jsdelivr.net/gh/MarionetTech/marionet_js_client@latest/marionet.js');

marionet('appKey', '<?php echo $marionet_plgn_options['app_key']; ?>');
<?php if ($info): ?>
marionet('setVisitorInfo', <?=json_encode($info)?>);
<?php endif; ?>
</script>
            <?php
        }
    }

    /** Submit product
     * @param $id
     * @param $name
     * @throws Exception
     */
    public static function productView()
    {
        $marionet_plgn_options = self::get_params();
        if(!empty($marionet_plgn_options['app_key']))
        {
            global $wp_query;
            $uri = get_permalink($wp_query->post);
            $product_cats = wp_get_post_terms( $wp_query->post->ID, 'product_cat', array( "fields" => "ids" ) );
            $category_id = (is_array($product_cats) && count($product_cats)) ? $product_cats[0] : 0;
            
            $product_id = $wp_query->post->ID;
            $product = wc_get_product( $product_id );
            if ($product->get_type() == 'variable') {
                $available_variations = $product->get_available_variations();
                if (count($available_variations) > 0) {
                    $variant = array_shift($available_variations);
                    $product_id = $variant['variation_id'];
                }
            }
            ?>
            <script type="text/javascript">
                marionet('event', 'view_product', {
                    product_id: <?php echo $product_id; ?>,
                    category_id: <?php echo $category_id; ?>,
                    product_name: '<?php echo $wp_query->post->post_title; ?>',
                    product_url: '<?php echo $uri; ?>'
                });
            </script>
            <?php
        }
    }

    /** Submit order state to marionet
     * @param $order_id
     */
    public static function stateOrder($order_id)
    {
        $order = wc_get_order($order_id);
        
        if(empty($order)) return;
        
        if(!($tracker = self::init_tracker())) return;
        
        $data = $order->get_data();
        
        // if order is created in the admin panel
        $format = 'd.m.y H:i:s';
        if (is_admin_bar_showing() and $data['date_created']->date($format) == $data['date_modified']->date($format)) self::submitOrder($order_id);
        else {
            $tracker->webHookOrderUpdate($order_id, self::switch_state( $order->get_status() ));
        }
    }

    /** Submit order delete to marionet
     * @param $order_id
     */
    public static function deleteOrder($order_id)
    {
        $order = wc_get_order($order_id);

        if(empty($order)) return;
    
        if(!($tracker = self::init_tracker())) return;
        
        $tracker->webHookOrderUpdate($order_id, 'cancelled');
    }

    /** Submit order to marionet
     * @param $order_id
     */
    public static function submitOrder($order_id)
    {
        $marionet_plgn_options = self::get_params();
        if(!empty($marionet_plgn_options['app_key']))
        { 
            require_once ( CONVEAD_PLUGIN_DIR . 'lib/MarionetClient.php');

            self::updateUserInfo();

            if (function_exists('wc_get_order')) $order = wc_get_order( $order_id );
            else $order = new WC_Order( $order_id );

            $visitor_info = array();
            $first_name = self::getValue($order->get_billing_first_name(), self::$userFirstName);
            if($first_name !== false){
                $visitor_info['first_name'] = $first_name;
            }

            $last_name = self::getValue($order->get_billing_last_name(), self::$userLastName);
            if($last_name !== false){
                $visitor_info['last_name'] = $last_name;
            }

            $email = self::getValue($order->get_billing_email(), self::$userEmail);
            if($email !== false){
                $visitor_info['email'] = $email;
            }

            $phone = self::getValue($order->get_billing_phone(), self::$userPhone);
            if($phone !== false){
                $visitor_info['phone'] = $phone;
            }

            if(!empty(self::$userDateOfBirth)){
                $visitor_info['date_of_birth'] = self::$userDateOfBirth;
            }
            if(!empty(self::$userGender)){
                $visitor_info['gender'] = self::$userGender;
            }

            do_action( 'marionet_visitor_info', $visitor_info );

            $guestUID = isset($_COOKIE['marionet_guest_uid']) ? $_COOKIE['marionet_guest_uid'] : false;
            
            if(is_admin_bar_showing()){
                $guestUID = false;
                self::$user_id = $order->customer_user ? $order->customer_user : false;
            }
            
            $client = new MarionetTracker(
                $marionet_plgn_options['app_key'],
                self::domain(),
                $guestUID,
                self::$user_id,
                $visitor_info
            );

            $items = array();


            $line_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );

            $total = $order->get_total();
            $shipping = (method_exists($order, 'get_total_shipping')) ? $order->get_total_shipping() : 0;
            $order_total = $total - $shipping;

            if(is_array($line_items) && count($line_items))
            {
                foreach ($line_items as $item_id => $item)
                {
                    $pid = !empty($item['variation_id'])
                        ? $item['variation_id'] : $item['product_id'];

                    $price = $item['line_subtotal']
                        / (float)$item['qty']
                        * $marionet_plgn_options['currency_excange_rate'];

                    $product = new stdClass();
                    $product->product_id = (int)$pid;
                    $product->qnt = (float)$item['qty'];
                    $product->price = $price;

                    $items[] = $product;
                }
            }

            $return = $client->eventOrder($order_id, $order_total, $items, self::switch_state( $order->get_status() ));
        }
    }

    private static function getValue($value, $default)
    {
        if(empty($value) && empty($default)){
            return false;
        }
        return (!empty($value)) ? $value : $default;
    }

    /** Submit cart to marionet
     * @param $order_number
     * @param $order_total
     * @param $items
     */
    public static function submitCart()
    {
        if(function_exists('WC')) $wc = WC();
        else
        {
            global $woocommerce;
            $wc = $woocommerce;
        }

        $marionet_plgn_options = self::get_params();
        if(!empty($marionet_plgn_options['app_key']))
        {
            require_once ( CONVEAD_PLUGIN_DIR . 'lib/MarionetClient.php');

            self::updateUserInfo();

            $visitor_info = array();
            if(!empty(self::$userFirstName)){
                $visitor_info['first_name'] = self::$userFirstName;
            }
            if(!empty(self::$userLastName)){
                $visitor_info['last_name'] = self::$userLastName;
            }
            if(!empty(self::$userEmail)){
                $visitor_info['email'] = self::$userEmail;
            }
            if(!empty(self::$userPhone)){
                $visitor_info['phone'] = self::$userPhone;
            }
            if(!empty(self::$userDateOfBirth)){
                $visitor_info['date_of_birth'] = self::$userDateOfBirth;
            }
            if(!empty(self::$userGender)){
                $visitor_info['gender'] = self::$userGender;
            }

            do_action( 'marionet_visitor_info', $visitor_info );

            $guestUID = isset($_COOKIE['marionet_guest_uid']) ? $_COOKIE['marionet_guest_uid'] : false;

            if (!$guestUID and !self::$user_id) return;

            $client = new MarionetTracker( $marionet_plgn_options['app_key'], self::domain(), $guestUID, self::$user_id, $visitor_info );

            $cart = $wc->cart->get_cart();

            $sessionCartValue = unserialize($wc->session->get('marionet_cart_value', ''));

            self::log('Event upldate cart '. date('Y-m-d h:i:s'));
            self::log('$sessionCartValue');
            self::log($sessionCartValue);
            
            $products = array();

            if(count($cart))
            {
                self::log('Count cart = '.count($cart));

                foreach ($cart as $k => $v)
                {
                    $pid = !empty($v['variation_id'])
                        ? $v['variation_id'] : $v['product_id'];

                    $price = $v['data']->get_price() * $marionet_plgn_options['currency_excange_rate'];
                    $products[] = array(
                        "product_id" => $pid,
                        "qnt" => $v['quantity'],
                        "price" => $price,
                    );
                }
            }


            if($sessionCartValue != $products)
            {
                self::log('Cart changed: '.serialize($products));
                $return = $client->eventUpdateCart($products);
                $wc->session->set('marionet_cart_value', serialize($products));
            }
        }
    }

    private static function updateUserInfo()
    {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_data = get_user_meta( $user_id );

        self::$user_id = $user_id;

        if(!empty($user_data['first_name'][0]))
            self::$userFirstName = $user_data['first_name'][0];
        if(!empty($user_data['last_name'][0]))
            self::$userLastName = $user_data['last_name'][0];
        if(!empty($current_user->data->user_email))
            self::$userEmail = $current_user->data->user_email;
        if(!empty($user_data['billing_phone'][0]))
            self::$userPhone = $user_data['billing_phone'][0];
    }


    private static function switch_state($state) {
        switch($state)
        {
          case 'wc-on-hold':
            $state = 'new';
            break;
          case 'wc-completed':
            $state = 'shipped';
            break;
          case 'wc-cancelled':
            $state = 'cancelled';
            break;
        }
        return $state;
    }

    private static function domain() {
        $urlparts = parse_url(site_url());
        return $urlparts['host'];
    }

    private static function get_params()
    {
        static $params;
        if(empty($params))
        {
            $params = get_option('marionet_plgn_options');
        }
        return $params;
    }

    private static function init_tracker()
    {
        $marionet_plgn_options = self::get_params();
        if(empty($marionet_plgn_options['app_key'])) return false;
        $app_key = $marionet_plgn_options['app_key'];
        require_once CONVEAD_PLUGIN_DIR . 'lib/MarionetTracker.php';
        $tracker = new MarionetTracker($app_key);
        return $tracker;
    }

    private static function log($data)
    {
        if(self::$log)
        {
            $data = print_r($data, true);
            $file = CONVEAD_PLUGIN_DIR .'log/log.txt';
            file_put_contents($file, PHP_EOL . $data, FILE_APPEND);
        }
    }
}