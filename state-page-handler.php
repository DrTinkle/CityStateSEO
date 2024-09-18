<?php

function get_state_abbreviation($state_name) {
    static $abbreviations = null;
    if (is_null($abbreviations)) {
        $abbreviations = [];
        $file_path = plugin_dir_path(__FILE__) . 'usstates.csv';
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {

                $abbreviations[strtolower(trim($data[0]))] = trim($data[1]);
            }
            fclose($handle);
        }
    }

    return $abbreviations[strtolower(trim($state_name))] ?? '';
}

function get_location_meta() {
    global $post;

    if (!(is_single() || is_page())) {
        return [
            'location_type' => '',
            'location_name' => '',
        ];
    }

    $location_type = get_post_meta($post->ID, 'location_type', true);
    $location_name = get_post_meta($post->ID, 'location_name', true);

    return [
        'location_type' => $location_type,
        'location_name' => $location_name,
    ];
}

function city_state_shortcode() {
    $location_meta = get_location_meta();

    if (is_front_page() || empty($location_meta['location_name'])) {
        return '';
    }

    if ($location_meta['location_type'] === 'city-state') {
        return ucwords(str_replace('-', ' ', $location_meta['location_name']));
    } elseif ($location_meta['location_type'] === 'state') {
        return ucwords($location_meta['location_name']);
    }

    return '';
}
add_shortcode('city_state', 'city_state_shortcode');

function in_city_state_shortcode() {
    $location_name = city_state_shortcode();
    if ($location_name !== '') {
        return " in " . $location_name;
    }
    return '';
}
add_shortcode('in_city_state', 'in_city_state_shortcode');

function hyph_city_state_shortcode() {
    $location_name = city_state_shortcode();
    if ($location_name !== '') {
        return " - " . $location_name;
    }

    return '';
}
add_shortcode('hyph_city_state', 'hyph_city_state_shortcode');

function location_name_shortcode() {
    $location_meta = get_location_meta();

    if (is_front_page() || empty($location_meta['location_name'])) {
        return '';
    }

    if ($location_meta['location_type'] === 'city-state') {
        $parts = explode(',', $location_meta['location_name']);
        $city = trim($parts[0]);
        return ucwords(str_replace('-', ' ', $city));
    } elseif ($location_meta['location_type'] === 'state') {
        return ucwords($location_meta['location_name']);
    }

    return '';
}
add_shortcode('location_name', 'location_name_shortcode');

function in_location_name_shortcode() {
    $location_name = location_name_shortcode();
    if ($location_name !== '') {
        return " in " . $location_name;
    }
    return '';
}
add_shortcode('in_location_name', 'in_location_name_shortcode');

function city_st_shortcode() {
    $location_meta = get_location_meta();

    if (is_front_page() || empty($location_meta['location_name'])) {
        return '';
    }

    if ($location_meta['location_type'] === 'city-state') {
        // Assuming the format "City, State" for the location_name
        list($city, $state) = explode(',', $location_meta['location_name'], 2);
        $abbreviation = get_state_abbreviation(trim($state));
        return ucwords(str_replace('-', ' ', trim($city))) . ', ' . strtoupper($abbreviation);
    } elseif ($location_meta['location_type'] === 'state') {
        // For state posts, just return the state name
        return ucwords($location_meta['location_name']);
    }

    return '';
}
add_shortcode('city_st', 'city_st_shortcode');

function in_city_st_shortcode() {
    $location_name = city_st_shortcode();
    if ($location_name !== '') {
        return " in " . $location_name;
    }
    return '';
}
add_shortcode('in_city_st', 'in_city_st_shortcode');

function location_usa_shortcode() {
    if (is_front_page()) return 'USA';

    global $post;
    $location_type = get_post_meta($post->ID, 'location_type', true);
    $location_name = get_post_meta($post->ID, 'location_name', true);

    // If it's not a single post or page, or no location type is defined, return a default message or empty.
    if (empty($location_type) || !is_single() && !is_page()) {
        return "Not available outside of posts/pages.";
    }

    $location_usa = 'USA'; // Default value
    if ($location_type === 'city-state') {
        // Assuming location_name is stored in format "City, State"
        $location_usa = "{$location_name}, USA";
    } elseif ($location_type === 'state') {
        // Assuming location_name is just the "State"
        $location_usa = "{$location_name}, USA";
    }

    return $location_usa;
}
add_shortcode('location_usa', 'location_usa_shortcode');

function generate_links($atts) {
    $atts = shortcode_atts(['part' => 1], $atts, 'generate_links');
    $part = max(1, intval($atts['part']));

    $location_meta = get_location_meta();
    $query_args = [
        'post_type' => 'post',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ];

    if ($location_meta['location_type'] === 'city-state') {
        // Fetch other cities within the same state
        $state = explode(',', $location_meta['location_name'])[1] ?? '';
        $query_args['tag'] = sanitize_title(trim($state));
        $query_args['category_name'] = 'cities';
    } elseif ($location_meta['location_type'] === 'state') {
        // Fetch cities within this state
        $state = $location_meta['location_name'];
        $state_slug = sanitize_title(trim($state));
        $query_args['tag'] = $state_slug;
        $query_args['category_name'] = 'cities';
    } elseif (is_front_page()) {
        // Fetch all states for the main page
        $query_args['category_name'] = 'states';
        unset($query_args['tag']);
    }

    $query = new WP_Query($query_args);
    $links = array_map(function($post) {
        return sprintf('<li><a href="%s">%s</a></li>', get_permalink($post->ID), get_the_title($post->ID));
    }, $query->posts);

    // Divide links into 6 parts
    $total_links = count($links);
    $links_per_part = ceil($total_links / 6);
    $part_links = array_slice($links, ($part - 1) * $links_per_part, $links_per_part);

    return '<ul>' . implode('', $part_links) . '</ul>';
}
add_shortcode('generate_links', 'generate_links');

