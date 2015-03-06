<?php
/*
Plugin Name: Weather Widget by Mixis.net
Plugin URI: http://www.mixis.net/weatherwidget
Description: Widget for display weather info from http://openweathermap.org/
Version: 1.0
Author: Mixis Web Solution
Author URI: http://www.mixis.net
License: GNU General Public License v2 or later
*/
?>
<?
function wp_weather_widget_mixis_setup()
{
	load_plugin_textdomain( 'wp-weather-widget-mixis', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action('plugins_loaded', 'wp_weather_widget_mixis_setup', 99999);

class WeatherWidgetMX extends WP_Widget {
	function __construct() {
		parent::__construct(
			'weather_widget_mx', // Base ID
			__( 'Weather Widget by Mixis', 'wp-weather-widget-mixis' ), // Name
			array( 'description' => __( 'Widget for display weather info', 'wp-weather-widget-mixis' ), ) // Args
		);
	}
	
	public function form($instance) {
		$title = "";
		$city_name_slug = "";
		$icon_color = "";
		if (!empty($instance)) {
			$title = esc_attr($instance["title"]);
			$city_name_slug = esc_attr($instance["city_name_slug"]);
			$icon_color = esc_attr($instance["icon_color"]);
		}

		echo '<p><label for="'.$this->get_field_id("title").'">'.__('Title', 'wp-weather-widget-mixis').'</label><br>';
		echo '<input id="'.$this->get_field_id("title").'" type="text" name="'.$this->get_field_name("title").'" value="'.$title .'"></p>';
 
		echo '<p><label for="'.$this->get_field_id("city_name_slug").'">'.__('City slug', 'wp-weather-widget-mixis').'</label><br>';
		echo '<input id="'.$this->get_field_id("city_name_slug").'" type="text" name="'.$this->get_field_name("city_name_slug").'" value="'.$city_name_slug .'"></p>';
		
		echo '<p><label for="'.$this->get_field_id("icon_color").'">'.__('Icon color', 'wp-weather-widget-mixis').'</label><br>';
		echo '<select id="'.$this->get_field_id("icon_color").'" name="'.$this->get_field_name("icon_color").'">';
		$options = array('white', 'black','color');
		foreach ($options as $option) {
			if($option=='white'){$text_color=__( 'White', 'wp-weather-widget-mixis' );}
			if($option=='black'){$text_color=__( 'Black', 'wp-weather-widget-mixis' );}
			if($option=='color'){$text_color=__( 'Color', 'wp-weather-widget-mixis' );}
			if ($icon_color==$option){$sel='selected="selected"';}else{$sel='';}
			echo '<option value="'.$option.'" id="'.$option.'" '.$sel.'>'.$text_color.'</option>';
		}
		echo '</select></p>';
	}
	
	public function update($newInstance, $oldInstance) {
		$values = array();
		$values["title"] = strip_tags($newInstance["title"]);
		$values["city_name_slug"] = strip_tags($newInstance["city_name_slug"]);
		$values["icon_color"] = strip_tags($newInstance["icon_color"]);
		return $values;
	}
	
	public function widget($args, $instance) {
		$title = $instance["title"];
		$city_name_slug = $instance["city_name_slug"];
		$icon_color = $instance["icon_color"];
		$weather_data=get_openweathermap_data($city_name_slug,$icon_color);
		echo '
			<li>
				<div class="footer-weather">
					<div class="footer-weather-header">'.$title.'</div>
					<div class="footer-weather-current-temp">'.$weather_data['temp'].'<sup>C</sup></div>
					<div class="footer-weather-todays-stats">
						<div class="footer-weather-todays-img"><img src="'.$weather_data['icon'].'"></div>
						<div class="footer-weather-todays-humidty">'.__('Humidity', 'wp-weather-widget-mixis').': '.$weather_data['humidity'].'%</div>
						<div class="footer-weather-todays-wind">'.__('Wind', 'wp-weather-widget-mixis').': '.$weather_data['wind'].' '.__('km/h', 'wp-weather-widget-mixis').'</div>
					</div>
				</div>
			</li>
		';
	}
}

function get_openweathermap_data($city_name_slug,$icon_color){
	if(!isset($icon_color))$icon_color='white';
	$sytem_locale = get_locale();
	if($sytem_locale=="uk_UK" || $sytem_locale=='uk') $sytem_locale="ua_UA";$locale = substr($sytem_locale, 0, 2);
	$weather_transient_name='awe_' . $city_name_slug . "_" . '_' . $locale;
	if( get_transient($weather_transient_name)){
		$city_data = get_transient( $weather_transient_name );
	}else{
		$now_ping = "http://api.openweathermap.org/data/2.5/weather?q=" . $city_name_slug . "&lang=" . $locale . "&units=metric";
		$now_ping_get = wp_remote_get( $now_ping );
		if( is_wp_error( $now_ping_get ) ){
			echo $now_ping_get->get_error_message(); 
		}
		$city_data = json_decode( $now_ping_get['body'] );
		set_transient($weather_transient_name,$city_data,3600);
	}
	$temp= round($city_data->main->temp); if($temp=='-0')$temp=0;if($temp>'0')$temp='+'.$temp;
	$humidity=round($city_data->main->humidity);
	$wind=$city_data->wind->speed;
	$description=$city_data->weather[0]->description;
	$dir=plugin_dir_url( __FILE__ );
	$icon=$dir.'/images/svg/'.$icon_color.'/'.$city_data->weather[0]->icon.'.svg';
	$output=array(
		'temp'=>$temp,
		'humidity'=>$humidity,
		'wind'=>$wind,
		'description'=>$description,
		'icon'=>$icon
	);
	return $output;
}

add_action( 'widgets_init', function(){
     register_widget( 'WeatherWidgetMX' );
});
?>