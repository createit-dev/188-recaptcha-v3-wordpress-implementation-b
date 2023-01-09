<?php

/**
 * CONFIGURATION:
 * - add to wp-config:
 * define( 'CT_RECAPTCHA_PUBLIC', 'XXX' );
 * define( 'CT_RECAPTCHA_SECRET', 'YYY' );
 */

/**
 * Add jQuery (if not enqueued already)
 */

add_action("init", "add_jquery_func");

function add_jquery_func()
{
    wp_enqueue_script("jquery");
}

/**
 * Add JS file to head
 */
function my_wp_head()
{
    echo '<script async src="https://www.google.com/recaptcha/api.js?render=' . CT_RECAPTCHA_PUBLIC . '"></script>';
}

add_action('wp_head', 'my_wp_head');

/**
 * AJAX submit
 */

add_action('wp_ajax_send_my_form', __NAMESPACE__ . 'send_form'); // This is for authenticated users
add_action('wp_ajax_nopriv_send_my_form', __NAMESPACE__ . 'send_form'); // This is for unauthenticated users.

function send_form()
{

    $alertMsg = '';

    if (empty($_POST["post_id"])) {
        $alertMsg = "Product ID required";
    }

    if (!(is_numeric($_POST["my_rating"]))) {
        $alertMsg = "Rating is required";
    }

    if (empty($_POST["token"])) {
        $alertMsg = "Token is required";
    }

    if (empty($_POST["action"])) {
        $alertMsg = "Action is required";
    }

    if ($alertMsg) {
        echo json_encode(array('success' => false, 'alert' => $alertMsg));
        wp_die();
    }


    // 1.0 is very likely a good interaction, 0.0 is very likely a bot
    $g_recaptcha_allowable_score = 0.3;
    $secretKey = CT_RECAPTCHA_SECRET;
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];


    // post request to server
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array('secret' => $secretKey, 'response' => $_POST["token"]);

    if (isset($ip)) {
        $data['remoteip'] = $ip;
    }

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $responseKeys = json_decode($response, true);

    //check if the test was done OK, if the action name is correct and if the score is above your chosen threshold (again, I've saved '$g_recaptcha_allowable_score' in config.php)
    if ($responseKeys["success"] && $responseKeys["action"] == $_POST["action"]) {
        if ($responseKeys["score"] >= $g_recaptcha_allowable_score) {

            /**
             * saving to database
             */


            /**
             * Update product with the score
             */

            $postId = intval($_POST["post_id"]);
            $myRating = intval($_POST["my_rating"]);

            if ($res = get_post_status($postId)) {
                // post exists
                // add not-unique post-meta
                add_post_meta($postId, 'my_rating', $myRating, false);
            }

            if ($res) {
                echo json_encode(array('success' => true, 'data' => $response));
            } else {
                echo json_encode(array('success' => false, 'alert' => 'Error 549'));
            }

        } elseif ($responseKeys["score"] < $g_recaptcha_allowable_score) {
            //failed spam test. Offer the visitor the option to try again or use an alternative method of contact.
            echo json_encode(array('success' => false, 'alert' => 'Error 553'));

        }
    } elseif ($responseKeys["error-codes"]) { //optional
        //handle errors. See notes below for possible error codes
        echo json_encode(array('success' => false, 'alert' => 'Error 554'));

    } else {
        //unkown screw up. Again, offer the visitor the option to try again or use an alternative method of contact.
        echo json_encode(array('success' => false, 'alert' => 'Error 556'));
    }

    die();

}
