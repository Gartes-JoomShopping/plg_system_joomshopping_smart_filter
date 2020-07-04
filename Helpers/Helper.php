<?php
	/**
	 * @package     SmartFilter
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */

	namespace SmartFilter\Helpers;


	use Exception;
	use Joomla\CMS\Factory;

	class Helper
	{
		/**
		 * @var \Joomla\CMS\Application\CMSApplication|null
		 * @since 3.9
		 */
		private $app;
		/**
		 * @var \JDatabaseDriver|null
		 * @since 3.9
		 */
		private $db;
		public static $instance;

		/**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct( $options = array() )
		{
			$this->app = Factory::getApplication();
			$this->db = Factory::getDbo();
			return $this;
		}#END FN

		/**
		 * @param array $options
		 *
		 * @return Helper
		 * @throws Exception
		 * @since 3.9
		 */
		public static function instance( $options = array() )
		{
			if( self::$instance === null )
			{
				self::$instance = new self( $options );
			}
			return self::$instance;
		}#END FN

		public function TitleEdit(){

			$title =  $this->getContentNode( 'title' );
			$h1 =  $this->getContentNode( 'h1' );
			$manufacturer_name = $this->getManufactureName();
			$characteristics_name = $this->getCharacteristicsName();






			if( !empty( $manufacturer_name ) )
			{
				$titleNew = $title . ' ' .'"'.$manufacturer_name.'"' ;
				$h1New = $h1 . ' ' .'"'.$manufacturer_name.'"' ;
			}#END IF

			if(   $characteristics_name   )
			{
				$titleNew = $title . ' ' .'"'.$characteristics_name.'"' ;
				$h1New = $h1 . ' ' .'"'.$characteristics_name.'"' ;
			}#END IF

			if( !empty( $manufacturer_name ) || $characteristics_name  )
			{
				$this->setContentNode( 'title' , $titleNew );
				$this->setContentNode( 'h1' , $h1New );
			}#END IF





//			echo'<pre>';print_r( $title );echo'</pre>'.__FILE__.' '.__LINE__;
//			echo'<pre>';print_r( $h1 );echo'</pre>'.__FILE__.' '.__LINE__;
//			echo'<pre>';print_r( $manufacturer_name );echo'</pre>'.__FILE__.' '.__LINE__;
//			die(__FILE__ .' '. __LINE__ );
		}

		/**
		 * Установить новое значение для тега
		 * @param $nodeName string Название тега
		 * @param $content
		 *
		 *
		 * @since version
		 */
		public function setContentNode( $nodeName , $content ){
			$body = $this->app->getBody();
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( mb_convert_encoding( $body , 'HTML-ENTITIES', 'UTF-8' ) );
			$dom->getElementsByTagName( $nodeName )->item(0)->nodeValue = $content;
			$body =  $dom->saveHTML() ;
			$this->app->setBody( $body ) ;
		}

		/**
		 * Получить значение тега
		 * @param $nodeName string Название тега
		 * @return mixed
		 *
		 * @since version
		 */
		public function getContentNode( $nodeName ){
			$body = $this->app->getBody();
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( mb_convert_encoding( $body , 'HTML-ENTITIES', 'UTF-8' ) );
			return $dom->getElementsByTagName( $nodeName )->item(0)->nodeValue ;
		}

		private function getCharacteristicsName(){
			$lang	= \JSFactory::getLang();
			$characteristics = $this->app->input->get('characteristics' , false , 'ARRAY' ) ;
			$firstEl = array_shift($characteristics);

			if( empty( $firstEl ) )
			{
				return false ;
			}#END IF

			$query 	= "SELECT
							`id` as `value`,
							`".$lang->get('name')."` as `text`
						FROM `#__jshopping_products_extra_field_values`
						WHERE `id` = ".$firstEl[0]."
						ORDER BY `".$lang->get('name')."`";
			$this->db->setQuery( $query );
			$res = $this->db->loadAssoc();
			return $res['text'] ;
		}

		/**
		 *
		 * @return mixed
		 *
		 * @since version
		 */
		private function getManufactureName(){
			$manufacturersID = ($this->app->input->get('manufacturers' , false , 'ARRAY' ))[0] ;
			$manufacturer = \JTable::getInstance('manufacturer', 'jshop');
			$manufacturer->load( $manufacturersID );
			$manufacturer_name = $manufacturer->getName();
			return $manufacturer_name ;
		}


	}