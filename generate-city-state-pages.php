<?php

function enqueue_css() {
    wp_enqueue_style('my-plugin-styles', plugin_dir_url(__FILE__) . 'citystate.css');
}
add_action('admin_enqueue_scripts', 'enqueue_css');

add_action('admin_menu', 'register_custom_menu_page');

function register_custom_menu_page() {
    add_menu_page('City/State SEO', 'City/State SEO', 'manage_options', 'city_state_posts', 
        'city_state_posts', '', 6);
    add_submenu_page('city_state_posts', 'Business Info', 'Business Info', 'manage_options', 
        'business_info', 'business_info_form');
    add_submenu_page('city_state_posts', 'Custom Post Types', 'Custom Post Types', 
        'manage_options', 'custom_post_types', 'custom_post_types_form');
    add_submenu_page('city_state_posts', 'Register State Flag Images', 'Register State Flag Images', 
        'manage_options', 'register_state_flag_images', 'register_state_flag_images_form');
    add_submenu_page('city_state_posts', 'About', 'About', 'manage_options', 
        'about_city_state', 'about_city_state');

}

add_action('admin_init', 'register_flags_upload');
function register_flags_upload() {
    // Check if nonce is set and verify it.
    if (isset($_POST['register_state_flags_nonce']) && wp_verify_nonce($_POST['register_state_flags_nonce'], 'register_state_flags_action')) {

        if (current_user_can('manage_options')) {
            upload_state_flags_and_save_ids();
            add_action('admin_notices', 'custom_admin_notice_success');
        }
    }
}

// Custom Admin Notice
function custom_admin_notice_success() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('State flags have been successfully uploaded and registered.', 'your-text-domain'); ?></p>
    </div>
    <?php
}

function register_state_flag_images_form() {
    ?>
    <div class="seo-wrap">
        <h2>Register State Flags</h2>
        <p>Click the button below to start the Wordpress registration process of state flags. Please note that this process may take some time.</p>
        <form method="post" action="">
            <?php wp_nonce_field('register_state_flags_action', 'register_state_flags_nonce'); ?>
            <?php submit_button('Register State Flags', 'primary', 'register_state_flags'); ?>
        </form>
    </div>
    <?php
}

function upload_state_flags_and_save_ids() {
    $csvFile = plugin_dir_path(__FILE__) . 'usstates.csv';
    $lines = file($csvFile);
    $states = [];

    // Extract state names from CSV, avoiding duplicates
    foreach ($lines as $line) {
        list($state) = explode(';', $line);
        $state = trim($state);
        if (!in_array($state, $states)) {
            $states[] = $state;
        }
    }

    // Include WordPress files for handling media
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $state_flags_ids = [];

    foreach ($states as $state) {
        $state_slug = sanitize_title_with_dashes($state);
        $flag_path = plugin_dir_path(__FILE__) . "state-flags/{$state_slug}.webp";
        $flag_url = plugins_url("/state-flags/{$state_slug}.webp", __FILE__);
    
        if (file_exists($flag_path)) {
            $attachment_id = media_sideload_image($flag_url, 0, $state . " Flag", 'id');
    
            if (!is_wp_error($attachment_id)) {
                $state_flags_ids[$state] = $attachment_id;
            } else {
                error_log("Error uploading state flag for $state: " . $attachment_id->get_error_message());
            }
        } else {
            error_log("Flag file not found for $state at path: $flag_path");
        }
    }

    update_option('state_flags_ids', $state_flags_ids);
}

// Get image ID for state flag
function get_flag_id($state_name) {
    $state_flags_ids = get_option('state_flags_ids', []);
    if (array_key_exists($state_name, $state_flags_ids)) {
        return $state_flags_ids[$state_name];
    } else {
        return null;
    }
}

// Displays the business information form
function business_info_form() {
    $saved_business_info = get_option('saved_business_info', []);

    // Define available business types from Schema.org
    $business_types = array(
        'Store' => 'Store',
        'AutomotiveBusiness' => 'Automotive Business',
        'ChildCare' => 'Child Care',
        'Dentist' => 'Dentist',
        'DryCleaningOrLaundry' => 'Dry Cleaning or Laundry',
        'EmergencyService' => 'Emergency Service',
        'EmploymentAgency' => 'Employment Agency',
        'EntertainmentBusiness' => 'Entertainment Business',
        'FinancialService' => 'Financial Service',
        'FoodEstablishment' => 'Food Establishment',
        'GeneralContractor' => 'General Contractor',
        'HealthAndBeautyBusiness' => 'Health and Beauty Business',
        'HomeAndConstructionBusiness' => 'Home and Construction Business',
        'InternetCafe' => 'Internet Cafe',
        'LegalService' => 'Legal Service',
        'Library' => 'Library',
        'LodgingBusiness' => 'Lodging Business',
        'MedicalBusiness' => 'Medical Business',
        'ProfessionalService' => 'Professional Service',
        'RealEstateAgent' => 'Real Estate Agent',
        'RecyclingCenter' => 'Recycling Center',
        'SelfStorage' => 'Self Storage',
        'ShoppingCenter' => 'Shopping Center',
        'SportsActivityLocation' => 'Sports Activity Location',
        'TouristInformationCenter' => 'Tourist Information Center',
        'TravelAgency' => 'Travel Agency',
    );


    ?>
    <div class="seo-wrap">

        <h3>Business Info</h3>

        <form method="post" class="seo-form">

        <input type="hidden" name="action" value="save_business_info">

            <!-- Business Name -->
            <label for="business_name">Business Name:</label>
            <input type="text" 
                name="business_name" 
                id="business_name" placeholder="Business Name" 
                value="<?php echo esc_attr($saved_business_info['business_name'] ?? ''); ?>" 
                class="regular-text" />

            <!-- Business Type Dropdown -->
            <label for="business_type">LocalBusiness Schema.org Subtypes:</label>
            <select 
                name="business_type" 
                id="business_type" 
                class="regular-text">
                <?php foreach ($business_types as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" 
                        <?php echo (isset($saved_business_info['business_type']) && 
                            $saved_business_info['business_type'] === $value) ? 'selected' : ''; ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Business Service/Product -->
            <label for="business_service">Primary Product or Service (input one only):</label>
            <input type="text" 
                name="business_service" 
                id="business_service" 
                placeholder="e.g., Consulting, Personal Training, Craft Supplies" 
                value="<?php echo esc_attr($saved_business_info['business_service'] ?? 
                    ''); ?>" class="regular-text" />

            <!-- Business Description -->
            <label for="business_description">Business Description:</label>
            <textarea 
                name="business_description"
                id="business_description"
                placeholder="A brief description of the business"
                class="regular-text"><?php echo esc_textarea(
                    $saved_business_info['business_description'] ?? ''); ?></textarea>

            <!-- Website URL -->
            <label for="business_website_url">Website URL:</label>
            <input type="url" 
                name="business_website_url" 
                id="business_website_url" 
                placeholder="https://www.example.com" 
                value="<?php echo esc_attr($saved_business_info['business_website_url'] ?? ''); ?>"
                class="regular-text" />

            <!-- Social Media URLs -->
            <label for="business_social_media">Social Media URLs:</label>
            <textarea 
                name="business_social_media" 
                id="business_social_media" 
                placeholder="Facebook, Twitter, LinkedIn, etc." 
                class="regular-text"><?php echo esc_textarea(
                    $saved_business_info['business_social_media'] ?? ''); ?></textarea>

            <!-- Opening Hours -->
            <label for="business_opening_hours">Opening Hours:</label>
            <input type="text" 
                name="business_opening_hours" 
                id="business_opening_hours" 
                placeholder="Mon-Fri 9:00-17:00"
                value="<?php echo esc_attr($saved_business_info['business_opening_hours'] ?? ''); ?>"  
                class="regular-text" />

            <!-- Logo URL -->
            <label for="business_logo_url">Logo URL:</label>
            <input type="url" 
                name="business_logo_url" 
                id="business_logo_url" 
                placeholder="https://www.example.com/logo.png" 
                value="<?php echo esc_attr($saved_business_info['business_logo_url'] ?? ''); ?>"  
                class="regular-text" />

            <input type="submit" 
                name="submit" 
                value="Save Business Info" 
                class="button button-primary">
        </form>
    </div>
    <?php
}
add_action('admin_init', 'save_business_info');

// Saves the business information submitted from the admin form
function save_business_info() {
    // Check if the correct form was submitted
    if (isset($_POST['action']) && $_POST['action'] === 'save_business_info') {
        // Sanitize and collect all business info from the form
        $business_info = [
            'business_name' => sanitize_text_field($_POST['business_name']),
            'business_type' => sanitize_text_field($_POST['business_type']),
            'business_service' => sanitize_text_field($_POST['business_service']),
            'business_description' => sanitize_textarea_field($_POST['business_description']),
            'business_website_url' => esc_url_raw($_POST['business_website_url']),
            'business_social_media' => sanitize_textarea_field($_POST['business_social_media']),
            'business_opening_hours' => sanitize_textarea_field($_POST['business_opening_hours']),
            'business_logo_url' => esc_url_raw($_POST['business_logo_url']),
        ];
        
        // Save the sanitized business info array as an option in WP database
        update_option('saved_business_info', $business_info);

        // Update the home page's SEO meta based on this business info
        $home_page_id = get_option('page_on_front');
        if ($home_page_id) {
            update_post_meta($home_page_id, '_seo_meta_title', $business_info['business_name'] . " - Home");
            update_post_meta($home_page_id, '_seo_meta_description', $business_info['business_description']);
        }

        // Feedback
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Business information saved successfully.</p></div>';
        });
    }
}

// Main function to display the City/State SEO Structure Generator page in WP admin
function city_state_posts() {
    
    // Determine which tab is currently active based on the 'tab' URL parameter
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'generate_states';

    ?>
    <div class="seo-wrap">
        <h1>City/State-Based SEO Structure Generator</h1>
        <h2 class="nav-tab-seo-wrapper">
            <!-- Navigation tabs for different actions -->
            <a href="?page=city_state_posts&tab=generate_states" 
                class="nav-tab <?php echo $active_tab == 'generate_states' ? 'nav-tab-active' : ''; ?>">Generate States</a>
            <a href="?page=city_state_posts&tab=generate_cities" 
                class="nav-tab <?php echo $active_tab == 'generate_cities' ? 'nav-tab-active' : ''; ?>">Generate Cities</a>
            <a href="?page=city_state_posts&tab=generate_articles" 
                class="nav-tab <?php echo $active_tab == 'generate_articles' ? 'nav-tab-active' : ''; ?>">Generate Articles</a>
            <a href="?page=city_state_posts&tab=delete_custom" 
                class="nav-tab <?php echo $active_tab == 'delete_custom' ? 'nav-tab-active' : ''; ?>">Custom Delete</a>
        </h2>

        <?php
        // Handle the action based on the form submission or active tab
        if (isset($_POST['action'])) {
            switch($_POST['action']){
                case 'generate_state_posts':
                    generate_state_posts();
                    break;
                case 'generate_city_posts':
                    generate_city_posts();
                    break;
                case 'generate_articles':
                    generate_articles();
                    break;
                case 'delete_custom':
                    process_custom_deletion();
                    break;
            }
        } else {
            switch($active_tab){
                case 'generate_states':
                    generate_state_posts_form();
                    break;
                case 'generate_cities':
                    generate_city_posts_form();
                    break;
                case 'generate_articles':
                    generate_articles_form();
                    break;
                case 'delete_custom':
                    delete_custom_form();
                    break;
                case 'about_city_state':
                    about_city_state();
                    break;
            }
        }
        ?>
    </div>
    <?php
}

// Injects JSON-LD structured data into the head of single posts
function insert_json_ld() {
    global $post;

    // Check if we're on a single post or the main front page.
    if (is_single() || is_front_page()) {
        $business_info = get_option('saved_business_info', []);
        
        $json_ld = [
            "@context" => "https://schema.org",
            "@type" => $business_info['business_type'] ?? '',
            "name" => $business_info['business_name'] ?? '',
            "description" => $business_info['business_description'] ?? '',
            "url" => $business_info['business_website_url'] ?? get_bloginfo('url'),
            "logo" => $business_info['business_logo_url'] ?? '',
        ];

        if (is_single()) {
            $location_type = get_post_meta($post->ID, 'location_type', true);
            $location_name = get_post_meta($post->ID, 'location_name', true);
            $schema_identifier = get_post_meta($post->ID, 'schema_identifier', true);
            $featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');

            $json_ld["name"] .= isset($location_name) ? " in " . $location_name : '';
            $json_ld["identifier"] = $schema_identifier;
            if ($featured_image_url) {
                $json_ld["image"] = $featured_image_url;
            }

            $product_service = $business_info['business_service'] ?? 'our services';
            $additional_description = ('state' === $location_type) ?
                " Specializing in {$product_service} across {$location_name}." :
                " Specializing in {$product_service} in {$location_name}.";
            $json_ld["description"] .= $additional_description;
        } else {
            $product_service = $business_info['business_service'] ?? 'our services';
            $additional_description = " Specializing in {$product_service}.";
            $json_ld["description"] .= $additional_description;
        }

        // Output the JSON-LD script in the head of the document
        echo '<script type="application/ld+json">' . json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}
add_action('wp_head', 'insert_json_ld');

function generate_state_posts_form() {
    ?>
    <div class="seo-wrap">
        <h2>Generate State Posts</h2>
        <p>Generate individual posts for all 50 US states with custom criteria (Plus 1 extra for DC).</p>
        <form method="post" class="seo-form">
            <!-- Hidden field to specify the action type -->
            <input type="hidden" name="action" value="generate_state_posts">
            
            <!-- Post Title Prefix (Focus Keyword) -->
            <label for="post_title_prefix">Post Title Prefix (Focus Keyword):</label>
            <input type="text" 
                name="post_title_prefix" 
                id="post_title_prefix" 
                placeholder="e.g., Sell My Business" 
                class="regular-text" />
            
            <!-- Post Author -->
            <label for="post_author">Post Author (User ID):</label>
            <input type="number" 
                name="post_author" 
                id="post_author" 
                placeholder="Default is current user ID" 
                class="regular-text" />
            
            <!-- Post Type -->
            <label for="post_type">Post Type (slug):</label>
            <input type="text" 
                name="post_type" 
                id="post_type" 
                placeholder="e.g., 'cs-post', 'state-article'. Default is 'post'"
                class="regular-text" />
            <p class="description">Please first add a Custom Post Type in the "Custom Post Types" submenu before proceeding with generation.</p>

            <!-- Category -->
            <label for="category">Additional Categories:</label>
            <input type="text" 
                name="category" 
                id="category" 
                placeholder="e.g., Business, Real Estate (Optional)" 
                class="regular-text" />
            <p class="description">Posts will automatically be categorized under "States". Specify any additional categories here, separated by commas.</p>

            <!-- Tags -->
            <label for="tags">Additional Tags:</label>
            <input type="text" 
                name="tags" 
                id="tags" 
                placeholder="e.g., Investment, Growth (Optional)" 
                class="regular-text" />
            <p class="description">The name of the state will automatically be included as tags. Specify any additional tags here, separated by commas.</p>

            <!-- Submit Button -->
            <input type="submit" 
                name="submit" 
                value="Generate State Posts" 
                class="button button-primary" 
                style="margin-top: 20px;">

            <!-- Reminder Text -->
            <p style="color: red; margin-top: 10px;">Reminder: Please do not refresh or close the page until the process completes.</p>
        </form>
    </div>
    <?php
}

// Handles the generation of state-specific posts based on the form inputs
function generate_state_posts() {
    if (isset($_POST['action']) && $_POST['action'] === 'generate_state_posts') {
        $posts_created = 0; // Counter for tracking number of posts created

        // Sanitize and store form inputs
        $post_title_prefix = sanitize_text_field($_POST['post_title_prefix']);
        $post_author = !empty($_POST['post_author']) ? intval($_POST['post_author']) : 
        get_current_user_id();
        $post_type = !empty($_POST['post_type']) ? sanitize_title_with_dashes($_POST['post_type']) : 'post';
        $category_name = sanitize_text_field($_POST['category']);
        $additional_tags = array_filter(array_map('trim', explode(',', sanitize_text_field(
            $_POST['tags']))));

        // Ensure "States" category exists or create it
        $states_category_id = get_cat_ID('States');
        if ($states_category_id == 0) {
            $states_category_id = wp_create_category('States');
        }

        // Handle additional category assignment from form input
        $category_id = get_cat_ID($category_name);
        if ($category_id == 0 && !empty($category_name)) {
            // Create new category if it does not exist
            $new_cat_id = wp_create_category($category_name);
            $category_id = $new_cat_id ? $new_cat_id : 0;
        }

        // Combine "States" category with additional category if specified
        $post_categories = array($states_category_id);
        if (!empty($category_id)) {
            $post_categories[] = $category_id;
        }

        // Load state names from the CSV file
        $csvFile = plugin_dir_path(__FILE__) . 'usstates.csv';
        $lines = file($csvFile);
        $states = [];

        // Extract state names from CSV, avoiding duplicates
        foreach ($lines as $line) {
            // Only extract the state from each line, ignoring abbrieviation
            list($state) = explode(';', $line);
            $state = trim($state);
        
            if (!in_array($state, $states)) {
                $states[] = $state;
            }
        }

        // Retrieve saved business info for inclusion in posts
        $business_info = get_option('saved_business_info', []);

        // Generate a post for each state with the customized content
        foreach ($states as $state) {
            $post_title = $post_title_prefix . ' ' . $state;
            $flag_id = get_flag_id($state);
            $tags = array_merge([$state], $additional_tags);
            $business_info = get_option('saved_business_info', []);
            $business_service = $business_info['business_service'] ?? 'our services';
            $post_content = "This post will offer detailed insights into {$business_service} within {$state}, designed to provide valuable information and guidance.";
        
            $post_data = [
                'post_name' => sanitize_title_with_dashes($post_title),
                'post_title'    => $post_title,
                'post_content'  => $post_content,
                'post_status'   => 'publish',
                'post_author'   => $post_author,
                'post_type'     => $post_type,
                'post_category' => $post_categories,
                'tags_input'    => $tags,
            ];
        
            $result = wp_insert_post($post_data);
            $business_description = $business_info['business_description'] ?? '';
        
            if ($result && !is_wp_error($result)) {

                $meta_description = "Gain insights into {$business_service} available in {$state}, offering a comprehensive overview and useful information. " . $business_description;
                $focus_keyword = $post_title;
                $schema_identifier = sanitize_title_with_dashes($post_title);

                set_post_thumbnail($result, $flag_id);

                update_post_meta($result, 'location_type', 'state');
                update_post_meta($result, 'location_name', $state);
                update_post_meta($result, 'meta_description', $meta_description);
                update_post_meta($result, 'focus_keyword', $focus_keyword);
                update_post_meta($result, 'schema_identifier', $schema_identifier);
        
                $posts_created++;
            }
        }

        if ($posts_created > 0) {
            echo '<div class="updated notice notice-success is-dismissible"><p>' . 
                $posts_created . ' state posts generated successfully.</p></div>';
        } else {
            echo '<div class="error notice notice-error is-dismissible"><p>No state posts were generated. Please check your inputs and try again.</p></div>';
        }
    }
}

function generate_city_posts_form() {
    ?>
    <div class="seo-wrap">
        <h2>Generate City Posts</h2>
        <p>Generate individual posts for the largest 60 cities in all 50 states with custom criteria.</p>
        <form method="post" class="seo-form">
            <!-- Hidden field to specify the action type -->
            <input type="hidden" name="action" value="generate_city_posts">

            <!-- Post Title Prefix (Focus Keyword) -->
            <label for="post_title_prefix">Post Title Prefix (Focus Keyword):</label>
            <input type="text" 
                name="post_title_prefix" 
                id="post_title_prefix" 
                placeholder="e.g., Sell My Business" 
                class="regular-text" />

            <!-- Post Author -->
            <label for="post_author">Post Author (User ID):</label>
            <input type="number" 
                name="post_author" 
                id="post_author" 
                placeholder="Default is current user ID" 
                class="regular-text" />
            
            <!-- Post Type -->
            <label for="post_type">Post Type (slug):</label>
            <input type="text" 
                name="post_type" 
                id="post_type" 
                placeholder="e.g., 'cs-post', 'city-article'. Default is 'post'"
                class="regular-text" />
            <p class="description">Please first add a Custom Post Type in the "Custom Post Types" submenu before proceeding with generation.</p>

            <!-- Category -->
            <label for="category">Additional Categories:</label>
            <input type="text" 
                name="category" 
                id="category" 
                placeholder="e.g., Tourism, Local Business (Optional)" 
                class="regular-text" />
            <p class="description">Posts will automatically be categorized under "Cities". Specify any additional categories here, separated by commas.</p>
          
            <!-- Tags -->
            <label for="tags">Additional Tags:</label>
            <input type="text" 
                name="tags" 
                id="tags" 
                placeholder="e.g., Investment, Growth (Optional)" 
                class="regular-text" />
            <p class="description">The name of the city and state will automatically be included as tags. Specify any additional tags here, separated by commas.</p>
            
            <!-- Batch Size -->
            <label for="batch_size">Batch Size:</label>
            <input type="number" 
                name="batch_size" 
                id="batch_size" 
                value="500" min="1" 
                max="5000" 
                class="regular-text" />
            <p class="description">To prevent server overload. Max value 5000</p>

            
            <!-- Submit Button -->
            <input type="submit" 
                name="submit" 
                value="Generate City Posts" 
                class="button button-primary" 
                style="margin-top: 20px;">
            
            <!-- Reminder Text -->
            <p style="color: red; margin-top: 10px;">Warning: A large amount of posts will be generated. <br>Please do not refresh or close the page until the process completes.</p>
        </form>
    </div>
    <?php
}

function generate_city_posts() {
    if (isset($_POST['action']) && $_POST['action'] === 'generate_city_posts') {
        $posts_created = 0; // Track the number of posts successfully created

        // Sanitize and store form inputs
        $post_title_prefix = sanitize_text_field($_POST['post_title_prefix']);
        $post_author = !empty($_POST['post_author']) ? intval($_POST['post_author']) : get_current_user_id();
        $post_type = !empty($_POST['post_type']) ? sanitize_title_with_dashes($_POST['post_type']) : 'post';
        $category_name = sanitize_text_field($_POST['category']);
        $additional_tags = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['tags']))));
        $batch_size = !empty($_POST['batch_size']) ? intval($_POST['batch_size']) : 500;
        $delay = 500000;

        // Ensure "Cities" category exists or create it
        $cities_category_id = get_cat_ID('Cities');
        if ($cities_category_id == 0) {
            $cities_category_id = wp_create_category('Cities');
        }

        // Handle additional category assignment from form input
        $category_id = get_cat_ID($category_name);
        if ($category_id == 0 && !empty($category_name)) {
            // Create new category if it does not exist
            $new_cat_id = wp_create_category($category_name);
            $category_id = $new_cat_id ? $new_cat_id : 0;
        }

        // Combine "Cities" category with additional category if specified
        $post_categories = array($cities_category_id);
        if (!empty($category_id)) {
            $post_categories[] = $category_id;
        }

        // Load state names from the CSV file
        $csvFile = plugin_dir_path(__FILE__) . 'uscities.csv';
        $lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $processed = 0;

        foreach ($lines as $line) {
            list($state, $city) = explode(';', $line);
            $state = trim($state);
            $city = trim($city);

            $flag_id = get_flag_id($state);

            // Fetch global business information to include in post content
            $business_info = get_option('saved_business_info', []);
            $business_service = $business_info['business_service'] ?? 'our services';
        
            // Construct the post title, tags, and content with the city and business information
            $post_title = $post_title_prefix . ' ' . $city . ' ' . $state;
            $city_state_tags = [$city, $state];
            $all_tags = array_merge($city_state_tags, $additional_tags);
            $post_content = "This post will offer detailed insights into {$business_service} within {$city}, {$state}, providing valuable information and guidance.";
            
            $post_data = [
                'post_title'    => $post_title,
                'post_content'  => $post_content,
                'post_status'   => 'publish',
                'post_author'   => $post_author,
                'post_type'     => $post_type,
                'post_category' => $post_categories,
                'tags_input'    => $all_tags,
            ];
            
            // Insert the post and update its meta information
            $result = wp_insert_post($post_data);
            $business_description = $business_info['business_description'] ?? '';
        
            if ($result && !is_wp_error($result)) {

                $meta_description = "Exploring " . $business_service . " in " . $city . ", " . $state . "? Find comprehensive insights and opportunities in " . $city . ". " . $business_description;
                $focus_keyword = $post_title;
                $schema_identifier = sanitize_title_with_dashes($post_title);

                set_post_thumbnail($result, $flag_id);

                update_post_meta($result, 'location_type', 'city-state');
                update_post_meta($result, 'location_name', $city . ', ' . $state);
                update_post_meta($result, 'meta_description', $meta_description);
                update_post_meta($result, 'focus_keyword', $focus_keyword);
                update_post_meta($result, 'schema_identifier', $schema_identifier);
        
                $posts_created++;
            }
            
            // Manage batch processing
            $processed++;
            if ($processed >= $batch_size) {
                usleep($delay);
                $processed = 0;
            }
        }

        if ($posts_created > 0) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($posts_created) . ' city posts generated successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>No city posts were generated. Please check the CSV file and your form inputs.</p></div>';
        }
    }
}

function generate_articles_form() {
    ?>
    <div class="seo-wrap">
        <h2>Generate Articles</h2>
        <p>Generate individual article posts for either all 50 states or the largest 60 cities in all 50 states with custom criteria.</p>
        <form method="post" class="seo-form">
            <!-- Hidden field to specify the action type -->
            <input type="hidden" name="action" value="generate_articles">

            <!-- Article Title -->
            <label for="article_title">Title:</label>
            <input type="text" 
                name="article_title" 
                id="article_title" 
                placeholder="e.g., Top Restaurants"
                class="regular-text" 
                required>
            <p class="description">This will be used as the primary title for each generated article.</p>

            <!-- Title Suffix -->
            <label for="title_suffix">Title Suffix:</label>
            <select 
                name="title_suffix" 
                id="title_suffix" 
                class="regular-text">
                <option value="state">State</option>
                <option value="city-state">City-State</option>
            </select>
            <p class="description">Determines if articles are for 'State' or 'City-State'. Default categories ('States' or 'Cities') will be applied based on this selection.</p>

            <!-- Article Category -->
            <label for="article_category">Aditional Categories:</label>
            <input type="text" 
                name="article_category" 
                id="article_category"
                placeholder="e.g., Dining Guides (Optional)" 
                class="regular-text" 
                required>
            <p class="description">Specify an additional category for the articles if needed.</p>

            <!-- Article Tags -->
            <label for="article_tags">Additional Tags:</label>
            <input type="text" 
                name="article_tags" 
                id="article_tags"
                placeholder="e.g., Fine Dining, Award Winning (Optional)"
                class="regular-text">
            <p class="description">These tags will be added to each generated article, in addition to default tags based on the article's state or city-state. Separate with commas.</p>

            <!-- Article Post Type -->
            <label for="article_post_type">Post Type (slug):</label>
            <input type="text" 
                name="article_post_type" 
                id="article_post_type" 
                placeholder="e.g., 'cs-post', 'article'. Default is 'post'"
                class="regular-text" />
            <p class="description">Please first add a Custom Post Type in the "Custom Post Types" submenu before proceeding with generation.</p>

            <!-- Batch Size -->
            <label for="batch_size">Batch Size:</label>
            <input type="number" 
                name="batch_size" 
                id="batch_size" 
                value="500" min="1" 
                max="5000" 
                class="regular-text" />
            <p class="description">To prevent server overload. Max value 5000</p>

            <!-- Submit Button -->
            <input type="submit" 
                value="Generate Articles" 
                name="submit" 
                class="button button-primary" 
                style="margin-top: 20px;">

            <!-- Reminder Text -->
            <p style="color: red; margin-top: 10px;">Warning: If "City-State" suffix is selected, a large amount of posts will be generated. <br>Please do not refresh or close the page until the process completes.</p>
        </form>
    </div>
    <?php
}

// Generate article posts based on the selected scope: state or city-state
function generate_articles() {
    if (isset($_POST['action']) && $_POST['action'] === 'generate_articles') {
        $title = sanitize_text_field($_POST['article_title']);
        $suffix = sanitize_text_field($_POST['title_suffix']);
        $tags = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['article_tags']))));
        $post_type = !empty($_POST['article_post_type']) ? sanitize_title_with_dashes($_POST['article_post_type']) : 'post';
        $batch_size = !empty($_POST['batch_size']) ? intval($_POST['batch_size']) : 500;
        $delay = 500000;
        $business_info = get_option('saved_business_info', []);

        // Determine the default category based on the suffix and retrieve/additional category from form
        $default_category_name = ($suffix === 'state') ? 'States' : 'Cities';
        $user_specified_category_name = sanitize_text_field($_POST['article_category']);

        // Ensure the default category exists or create it
        $default_category_id = get_cat_ID($default_category_name);
        if (!$default_category_id) {
            $default_category_id = wp_create_category($default_category_name);
        }

        // Ensure the user-specified category exists or create it
        $user_specified_category_id = get_cat_ID($user_specified_category_name);
        if (!$user_specified_category_id && !empty($user_specified_category_name)) {
            $user_specified_category_id = wp_create_category($user_specified_category_name);
        }

        // Use both category IDs for the post
        $post_category_ids = [$default_category_id];
        if ($user_specified_category_id) {
            $post_category_ids[] = $user_specified_category_id;
        }

        // Load state or city names from the CSV file
        $csvFile = plugin_dir_path(__FILE__) . 'uscities.csv';
        $lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $processed = 0;
        $articlesCreated = 0; // Counter for successfully created articles
        $statesProcessed = [];

        foreach ($lines as $line) {
            list($state, $city) = explode(';', $line);

            // Skip processing if generating state articles and the state has already been processed
            if ($suffix === 'state' && in_array($state, $statesProcessed)) continue;

            // Construct the article title based on the suffix choice
            $title_suffix = $suffix === 'state' ? $state : "$city, $state";
            $post_title = $title . " in " . $title_suffix;
            $final_tags = $suffix === 'state' ? array_merge([$state], $tags) : array_merge([$city, $state], $tags);
            
            $post_data = [
                'post_title'    => $post_title,
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => $post_type,
                'post_category' => $post_category_ids,
                'tags_input'    => $final_tags,
            ];

            $result = wp_insert_post($post_data);
            $business_description = $business_info['business_description'] ?? '';
            
            if (!is_wp_error($result)) {

                $location_type = $suffix === 'state' ? 'state' : 'city-state';
                $location_name = $suffix === 'state' ? $state : "$city, $state";

                $meta_description = "An article about " . $title . " and " . $business_info['business_service'] . " in " . $location_name . ". " . $business_description;
                $focus_keyword = $post_title;

                update_post_meta($result, 'location_type', $location_type);
                update_post_meta($result, 'location_name', $location_name);
                update_post_meta($result, 'meta_description', $meta_description);
                update_post_meta($result, 'focus_keyword', $focus_keyword);

                $articlesCreated++;
            }

            if ($suffix === 'state') {
                $statesProcessed[] = $state;
            }

            $processed++;
            if ($processed >= $batch_size) {
                usleep($delay);
                $processed = 0;
            }
        }

        echo '<div class="updated"><p>Article generation process completed. ' . $articlesCreated . ' articles were created.</p></div>';
    }
}

function delete_custom_form() {
    ?>
    <div class="seo-wrap">
        <h2>Custom Delete</h2>
        <p>Delete posts based on specified criteria.</p>
        <form method="post" class="seo-form">
            <!-- Hidden field to specify the action type -->
            <input type="hidden" name="action" value="delete_custom">

            <!-- Post Type -->
            <label for="post_type">Post Type (slug):</label>
            <input type="text" 
                name="post_type" 
                id="post_type" 
                placeholder="e.g., post" 
                class="regular-text" />
            <p class="description">Check the 'Custom Post Types' submenu.</p>

            <!-- Category -->
            <label for="category">Category (slug):</label>
            <input type="text" 
                name="category" 
                id="category" 
                placeholder="e.g., states" 
                class="regular-text" />

            <!-- Tags -->
            <label for="tags">Tags (comma-separated slugs):</label>
            <input type="text" 
                name="tags" 
                id="tags" 
                placeholder="e.g., wordpress, plugin" 
                class="regular-text" />

            <!-- Author -->
            <label for="author">Author (User ID):</label>
            <input type="number" 
                name="author" 
                id="author" 
                placeholder="e.g., 1" 
                class="regular-text" />

            <!-- Batch Size -->
            <label for="batch_size">Batch Size:</label>
            <input type="number" 
                name="batch_size" 
                id="batch_size" 
                value="500" min="1" 
                max="5000" 
                class="regular-text" />
            <p class="description">To prevent server overload. Max value 5000</p>

            <!-- Submit Button -->
            <input type="submit" 
                name="submit" 
                value="Delete Posts" 
                class="button button-primary" 
                style="margin-top: 20px;" 
                onclick="return confirm('Are you sure? This process is irreversible.');">

            <!-- Reminder Text -->
            <p style="color: red; margin-top: 10px;">Reminder: Please do not refresh or close the page until the process completes. If deleting a large amount of posts, please adjust the Batch Size accordingly.</p>
        </form>
    </div>
    <?php
}

// Handles the deletion of posts based on the field data
function process_custom_deletion() {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_custom') {
        // Sanitize input data
        $post_type = sanitize_text_field($_POST['post_type']);
        $category_slug = sanitize_text_field($_POST['category']);
        $tags = sanitize_text_field($_POST['tags']);
        $author = !empty($_POST['author']) ? intval($_POST['author']) : '';
        $batch_size = !empty($_POST['batch_size']) ? intval($_POST['batch_size']) : 500;
        $delay = 500000; // Delay to prevent server overload

        $total_posts_deleted = 0; // Initialize counter for deleted posts

        do {
            $args = [
                'post_type'      => !empty($post_type) ? $post_type : 'any',
                'posts_per_page' => $batch_size,
                'fields'         => 'ids',
                'no_found_rows'  => true, // Skip pagination to improve performance
            ];

            // Add category filter if specified.
            if (!empty($category_slug)) {
                $args['category_name'] = $category_slug;
            }

            // Add tag filter if specified.
            if (!empty($tags)) {
                $tag_slugs = explode(',', $tags);
                $args['tag_slug__in'] = $tag_slugs;
            }

            // Add author filter if specified.
            if (!empty($author)) {
                $args['author'] = $author;
            }

            $query = new WP_Query($args);
            $posts_deleted = 0;

            // Delete the posts retrieved by the query.
            foreach ($query->posts as $post_id) {
                wp_delete_post($post_id, true);
                $posts_deleted++;
            }

            $total_posts_deleted += $posts_deleted;

            // Pause execution to prevent server overload if posts were deleted.
            if ($posts_deleted > 0) {
                usleep($delay);
            }

        } while ($posts_deleted > 0); // Repeat if there were posts deleted in the last batch.

        if ($total_posts_deleted > 0) {
            echo '<div class="updated"><p>Deletion process completed. Total posts deleted: ' . esc_html($total_posts_deleted) . '.</p></div>';
        } else {
            echo '<div class="error"><p>No posts found matching the specified criteria or all matching posts have already been deleted.</p></div>';
        }
    }
}

function custom_post_types_form() {
    // Retrieve the custom post types from the option
    $custom_post_types = get_option('custom_post_types_for_urls', []);
    ?>
    <div class="seo-wrap">
        <h1>Custom Post Types</h1>        
        <form method="post" class="seo-form" action="">
            <?php wp_nonce_field('add_custom_post_type_action', 'add_custom_post_type_nonce'); ?>
            <h2>Add New Custom Post Type</h2>

            <!-- Post Type -->
            <label for="post_type">Post Type:</label>
            <input type="text" 
                name="post_type" 
                id="post_type" 
                placeholder="e.g., 'CS Post', 'Article'"
                class="regular-text" />

            <!-- Post Type Plural -->
            <label for="post_type_plural">Post Type Plural:</label>
            <input type="text" 
                name="post_type_plural" 
                id="post_type_plural" 
                placeholder="e.g., 'CS Posts', 'Articles'. Default is [Post Type]s"
                class="regular-text" />

            <label for="rewrite_rule">Optimize URL Structure:</label>
            <input type="checkbox" 
                id="rewrite_rule" 
                name="rewrite_rule" 
                value="1" 
                checked>
            <p class="description">
                Enable to attempt cleaner URLs by removing the custom post type slug. <br>Test thoroughly, as it may not work in all setups.
            </p>
            <button type="submit" name="add_new_cpt" class="button button-primary">Add Custom Post Type</button>
        </form>

        <h2>Registered Custom Post Types</h2>
        <ul>
            <?php foreach ($custom_post_types as $cpt) {
                $readableName = ucwords(str_replace('-', ' ', sanitize_text_field($cpt['singular'])));
                $query = new WP_Query(['post_type' => $cpt['singular'], 'post_status' => 'publish', 'posts_per_page' => -1]);
                $post_count = $query->found_posts;
                ?>
                <li>
                    <form method="post" action="" onsubmit="return confirmDelete();">
                        <?php wp_nonce_field('delete_custom_post_type_action', 'delete_custom_post_type_nonce'); ?>
                        <strong>Post Type:</strong> <?= $readableName ?>
                        (<strong>Slug:</strong> <?= esc_html($cpt['singular']) ?>, <strong>Plural:</strong> <?= esc_html($cpt['plural']) ?>)
                        - Posts: <?= $post_count ?>
                        - <button type="submit" name="delete_cpt" value="<?= esc_attr($cpt['singular']) ?>">Delete</button>
                    </form>
                </li>
            <?php } ?>
        </ul>
        <p style="color: red; margin-top: 20px;"><strong>Important:</strong> Deleting a custom post type will <strong>not automatically delete</strong> the posts associated with that post type. Before deleting, please ensure that you have either <strong>moved or deleted all posts</strong> under this post type to avoid orphaned data. Consider exporting or backing up the posts if they are needed in the future. Proceed with caution.</p>
    </div>

    <script type="text/javascript">
    function confirmDelete() {
        return confirm("Are you sure you want to delete this custom post type? This action cannot be undone. Please ensure all posts are deleted or moved before proceeding.");
    }
    </script>
    <?php
}

add_action('admin_init', 'delete_custom_post_type_handler');
function delete_custom_post_type_handler() {
    if (isset($_POST['delete_cpt']) && check_admin_referer('delete_custom_post_type_action', 'delete_custom_post_type_nonce')) {
        $custom_post_types = get_option('custom_post_types_for_urls', []);

        // Filter out the post type to be deleted
        $new_custom_post_types = array_filter($custom_post_types, function($cpt) {
            return $cpt['singular'] !== $_POST['delete_cpt'];
        });

        // Update the option with the new array
        update_option('custom_post_types_for_urls', $new_custom_post_types);

        // Redirect to avoid resubmissions
        wp_redirect(admin_url('admin.php?page=custom_post_types'));
        exit;
    }
}

add_action('admin_init', 'add_custom_post_type');
function add_custom_post_type() {
    if (isset($_POST['add_new_cpt']) && check_admin_referer('add_custom_post_type_action', 'add_custom_post_type_nonce')) {
        $post_type = sanitize_text_field($_POST['post_type']);
        $post_type_plural = !empty($_POST['post_type_plural']) ? sanitize_text_field($_POST['post_type_plural']) : sanitize_text_field($_POST['post_type']) . 's';
        $rewrite_rule = isset($_POST['rewrite_rule']) ? (bool)$_POST['rewrite_rule'] : false;

        $post_type_slug = sanitize_title_with_dashes($post_type);

        if ($post_type_slug === 'post' || empty($post_type_slug)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>Invalid post type specified. Please try again.</p></div>';
            });
            return;
        }

        // Fetch the existing custom post types to check for duplicates
        $custom_post_types = get_option('custom_post_types_for_urls', []);
        foreach ($custom_post_types as $cpt) {
            if ($cpt['singular'] === $post_type_slug) {
                // Post type already exists, abort and optionally notify the user
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-warning is-dismissible"><p>This custom post type already exists.</p></div>';
                });
                return;
            }
        }

        // Post type does not exist, so add it
        $custom_post_types[] = ['singular' => $post_type_slug, 'plural' => $post_type_plural, 'rewrite' => $rewrite_rule];
        update_option('custom_post_types_for_urls', $custom_post_types);

        flush_rewrite_rules();

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>New custom post type added successfully.</p></div>';
        });
    }
}

add_action('init', 'register_custom_post_types_from_option');
function register_custom_post_types_from_option() {

    $custom_post_types = get_option('custom_post_types_for_urls', []);

    foreach ($custom_post_types as $cpt) {
        $post_type = $cpt['singular'];
        $post_type_plural = $cpt['plural'];

        $labels = array(
            'name'                  => _x($post_type, 'Post Type General Name', 'text_domain'),
            'singular_name'         => _x($post_type, 'Post Type Singular Name', 'text_domain'),
            'menu_name'             => __($post_type_plural, 'text_domain'),
            'name_admin_bar'        => __('Post Type', 'text_domain'),
            'archives'              => __('Item Archives', 'text_domain'),
            'attributes'            => __('Item Attributes', 'text_domain'),
            'parent_item_colon'     => __('Parent Item:', 'text_domain'),
            'all_items'             => __('All Items', 'text_domain'),
            'add_new_item'          => __('Add New Item', 'text_domain'),
            'add_new'               => __('Add New', 'text_domain'),
            'new_item'              => __('New Item', 'text_domain'),
            'edit_item'             => __('Edit Item', 'text_domain'),
            'update_item'           => __('Update Item', 'text_domain'),
            'view_item'             => __('View Item', 'text_domain'),
            'view_items'            => __('View Items', 'text_domain'),
            'search_items'          => __('Search Item', 'text_domain'),
            'not_found'             => __('Not found', 'text_domain'),
            'not_found_in_trash'    => __('Not found in Trash', 'text_domain'),
            'featured_image'        => __('Featured Image', 'text_domain'),
            'set_featured_image'    => __('Set featured image', 'text_domain'),
            'remove_featured_image' => __('Remove featured image', 'text_domain'),
            'use_featured_image'    => __('Use as featured image', 'text_domain'),
            'insert_into_item'      => __('Insert into item', 'text_domain'),
            'uploaded_to_this_item' => __('Uploaded to this item', 'text_domain'),
            'items_list'            => __('Items list', 'text_domain'),
            'items_list_navigation' => __('Items list navigation', 'text_domain'),
            'filter_items_list'     => __('Filter items list', 'text_domain'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => $post_type),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'show_in_rest'       => true,
            'taxonomies'         => array('category', 'post_tag'),
        );

        // Check if this post type is already registered to avoid errors
        if (!post_type_exists($post_type)) {
            register_post_type($post_type, $args);
        }
    }
}

function custom_rewrite_rules() {
    $custom_post_types = get_option('custom_post_types_for_urls', []);
    foreach ($custom_post_types as $cpt) {
        if (!isset($cpt['rewrite']) || !$cpt['rewrite']) {
            continue; // Skip adding rewrite rules if the toggle is off
        }
        
        add_rewrite_rule(
            '^' . $cpt['singular'] . '/([^/]+)(?:/([0-9]+))?/?$',
            'index.php?post_type=' . $cpt['singular'] . '&name=$matches[1]',
            'top'
        );
    }
}
add_action('init', 'custom_rewrite_rules');

function filter_post_type_link($post_link, $id = 0) {
    $post = get_post($id);
    $custom_post_types = get_option('custom_post_types_for_urls', []);
    foreach ($custom_post_types as $cpt) {
        if ($post->post_type == $cpt['singular'] && !empty($cpt['rewrite'])) {
            return home_url('/' . $post->post_name . '/');
        }
    }
    return $post_link;
}
add_filter('post_type_link', 'filter_post_type_link', 10, 2);

function about_city_state() {
    ?>
    <div class="seo-wrap">
        <h1>About City/State SEO Plugin</h1>
        <p>The City/State SEO Plugin is designed to enhance local SEO for businesses by enabling the creation of city- and state-specific posts and incorporating structured data for improved search engine visibility. Tailored to meet the needs of businesses looking to boost their local online presence, the plugin offers a comprehensive solution for local SEO optimization.</p>
        <ul>
            <li>- Facilitates the automated generation of custom posts for every city and state, enriching your website with localized content that attracts more visitors.</li>
            <li>- Automatically inserts structured data into posts, enhancing search engine comprehension and improving local search rankings.</li>
            <li>- Offers flexibility and customization for creating post types and structured content that best serve your business's online strategy.</li>
            <li>It is recommended to use templates for posts with provided shortcodes for enhanced content presentation and SEO advantages. Shortcodes include:
                <ul>
                    <li><code>[location_name]</code> and <code>[in_location_name]</code> for displaying the location's name.</li>
                    <li><code>[location_usa]</code> for displaying the location's name followed by ", USA".</li>
                    <li><code>[city_st]</code> and <code>[in_city_st]</code> for displaying the city name and state abbreviation.</li>
                    <li><code>[city_state]</code> and <code>[in_city_state]</code> for displaying the full city name and state.</li>
                </ul>
                Each shortcode defaults to the State name if the city is not found, and to an empty string if neither is found. This ensures that your content remains accurate and relevant, even when specific location data is unavailable.
            </li>
        </ul>
        <h2>How to Use</h2>
        <p>For optimal use of the City/State SEO Plugin, we recommend starting by entering your business details in the 'Business Info' submenu. This foundational step ensures that all generated content aligns with your business's information and SEO goals. Following this, proceed to the 'Custom Post Types' submenu to create your desired post types. Then, utilize the generation tabs to create location-specific posts, enhancing your site's local SEO footprint. For maximum impact, apply templates coupled with our shortcodes for dynamic and SEO-effective content.</p>
        <h2>Support and Assistance</h2>
        <p>Should you need help or wish to explore more about how the City/State SEO Plugin can benefit your business, please reach out to us at info@acornbusinessbuyers.com. Our dedicated support team is eager to assist you in maximizing your local SEO efforts and achieving your online visibility goals.</p>
    </div>
    <?php
}

