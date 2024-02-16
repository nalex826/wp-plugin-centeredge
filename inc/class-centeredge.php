<?php
/**
 * WP Custom CenterEdge Class
 */
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/* Check if Class Exists. */
if (! class_exists('CenterEdge')) {
    class CenterEdge
    {
        /**
         * The URL for fetching data from AWS.
         */
        const AWSURL = 'http://ec2-34-200-14-116.compute-1.amazonaws.com/data_times.json';

        /**
         * CenterEdge constructor.
         * Initializes the scraping process.
         */
        public function __construct()
        {
            // Handle scraping of complex single pages of Centeredge Pages
            $this->complexScrap();
        }

        /**
         * Fetches content from a given URL using cURL.
         *
         * @param string $url the URL to fetch content from
         *
         * @return mixed|string the fetched content
         */
        private function load_content($url)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            curl_close($ch);

            return $response;
        }

        /**
         * Scrapes complex single entry pages.
         * Fetches data from AWS, processes it, and inserts into the database.
         */
        private function complexScrap()
        {
            // Fetch codes from ACF field
            $codes = get_field('center_edge_complex_feeds', 'options');

            // Write codes to file (for debugging)
            file_put_contents(dirname(__FILE__) . '/codes.json', json_encode($codes));

            if (empty($codes)) {
                return;
            }

            // Fetch data from AWS
            $data  = $this->load_content(self::AWSURL);
            $links = json_decode($data);

            if (! empty($links)) {
                // Insert data into the database
                $this->insertDb($links);
            }
        }

        /**
         * Inserts scraped data into the database.
         *
         * @param array $links the data to insert
         */
        private function insertDb($links)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'centeredge_booking';

            // Clear out existing entries
            $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE `type` = %s", 'complex'));
            $wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = 1");

            foreach ($links as $items) {
                $insert_sql = [];
                foreach ($items as $entry) {
                    $insert_sql[] = [
                        'type'     => 'complex',
                        'name'     => $entry[1],
                        'posted'   => $entry[2],
                        'link'     => $entry[3],
                        'ticket'   => $entry[4],
                        'outstock' => $entry[5],
                    ];
                }
                if (! empty($insert_sql)) {
                    // Insert data into the database using prepared statements
                    $wpdb->insert($table_name, $insert_sql);
                }
            }
        }
    }
}
