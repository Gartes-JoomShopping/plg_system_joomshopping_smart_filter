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
         * @var array
         * @since version
         */
        private $params;
        /**
         * @var mixed
         * @since version
         */
        private $RootCategory;
        private $ParentCategory;
        private $CurrentCategory;
        /**
         * @var string|string[]
         * @since version
         */
        private $h1;
        /**
         * @var mixed
         * @since version
         */
        private $City;
        /**
         * @var string|string[]
         * @since version
         */
        private $Title;

        private $pregArr = [
            '{RootCategory}',
            '{ParentCategory}',
            '{CurrentCategory}',
            '{H1}',
            '{City}',
            '{FilterStringParam}',
        ];
        
        /**
         * @var string
         * @since version
         */
        private $FilterStringParam = '';



        /**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct( $options = array() )
		{
            $this->params = $options ;
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

		public function TitleEdit( $filterData ){

		    $lang	= \JSFactory::getLang();

            \JLoader::registerNamespace('CountryFilter',JPATH_PLUGINS.'/system/country_filter',$reset=false,$prepend=false,$type='psr4');
            $city = $this->app->input->get('sitecountry' , 'moskva' );

            # Получить даные для выбранного города
            $this->City =  ( \CountryFilter\Helpers\CitiesDirectory::getLocationByCityName( $city ) )['cities'] ;

            $CategoryTable = $this->getCatrgory();

            $firstFilterGroup = $this::_array_key_first( $filterData );
            if (count($filterData) == 1) {
                switch ($firstFilterGroup) {
                    case 'manufacturers' :

                        if (count($filterData['manufacturers']) > 1) break; #END IF
                        $First = array_shift($filterData['manufacturers']);
                        $manufacturer_name = $this->getManufactureName($First);
                        $manufactere_label = $this->params->get('manufactere_label', 'Производитель');
                        $this->FilterStringParam = $manufactere_label . ' "' . $manufacturer_name . '"';

                        break;
                    case 'characteristics' :

                        if (count($filterData['characteristics']) > 1) break; #END IF
                        $characteristicsGroupID = $this::_array_key_first($filterData['characteristics']);
                        if (count($filterData['characteristics'][$characteristicsGroupID]) > 1) return; #END IF

                        $fields = \JSFactory::getAllProductExtraField();
                        $characteristicsName = $fields[$characteristicsGroupID]->name;
                        $value = array_shift($filterData['characteristics'][$characteristicsGroupID]);


                        # Если в строке нет букв
                        $chr_ru_en = "A-zА-Яа-яЁё";
                        if (!preg_match("/[$chr_ru_en]/u", $value)) {
                            $Query = $this->db->getQuery(true);
                            $whereArr = [
                                $this->db->quoteName('id') . ' = ' . $this->db->quote($value),
                                $this->db->quoteName('field_id') . ' = ' . $this->db->quote($characteristicsGroupID),
                            ];
                            $Query->select('*')
                                ->from($this->db->quoteName('#__jshopping_products_extra_field_values'))
                                ->where($whereArr);
                            $this->db->setQuery($Query);

                            $res = $this->db->loadAssoc();
                            if (isset($res['name_ru-RU'])) {
                                $value = $res['name_ru-RU'];
                            }#END IF
                        }
                        $this->FilterStringParam = $characteristicsName . ' "' . $value . '"';
                        break;

                    default :
                }
            }#END IF




            #Если тэг h1 пустой загружаем шаблон из настроек плагина
            # Иначе принимаем тэг h1 как шаблон
            $h1 = $this->getContentNode('h1');
            if (empty($h1)) {
//                $template_h1 = $this->params->get('template_h1', $h1);
            }else{
                $title = $CategoryTable->{$lang->get('name')};
                $template_h1 = $title ;
            }#END IF

            # Создание создание h1

            $data_H1 = [
                $this->RootCategory,
                $this->ParentCategory,
                $this->CurrentCategory,
                '',
                $this->City,
                $this->FilterStringParam,
            ];

            $this->h1 = $this->loadDataTemplate( $template_h1 ) ;

//            echo'<pre>';print_r( $this->h1 );echo'</pre>'.__FILE__.' '.__LINE__;
//            die(__FILE__ .' '. __LINE__ );


//            $this->h1 = str_replace($this->pregArr, $data_H1, $template_h1);
//            $this->h1 = $this->_cleanTags($this->h1);
            $this->setContentNode('h1', $this->h1);
            


            
            $data = [
                $this->RootCategory,
                $this->ParentCategory,
                $this->CurrentCategory,
                $this->h1,
                $this->City,
            ];

            # Создание тайтла
//            $title = $this->getContentNode('title');
            $title = $CategoryTable->{$lang->get('meta_title')};
            if (empty($title)) {

                $template_Title = $this->params->get('template_title', $title);
                $this->Title = $this->loadDataTemplate( $template_Title ) ;
//                $this->Title = str_replace($this->pregArr, $data, $template_Title);
//                $this->Title = $this->_cleanTags($this->Title);
                $this->setContentNode('title', $this->Title);
            }else{
                $this->Title = $this->loadDataTemplate( $title ) ;
                $this->setContentNode('title', $this->Title );
            }

//            $description = $this->getContentNode('description');
            $description = $CategoryTable->{$lang->get('meta_description')};
            if (empty($description)) {
                $template_Description = $this->params->get('template_description', $description);
                $this->Description = $this->loadDataTemplate( $template_Description ) ;

//                $this->Description = str_replace($this->pregArr, $data, $template_Description);
//                $this->Description = $this->_cleanTags($this->Description);
                $this->setContentNode('description', $this->Description);
            }else{
                $this->Description = $this->loadDataTemplate( $description ) ;
                $this->setContentNode('description', $this->Description);
            }


            $this->correctBreadcrumbs();


        }
        protected function loadDataTemplate( $template ){

            $template = str_replace('[[[CITY]]]' , '{City}' , $template ) ;
            $data = [
                $this->RootCategory,
                $this->ParentCategory,
                $this->CurrentCategory,
                $this->h1,
                $this->City,
                $this->FilterStringParam ,
            ];
            $res = trim( str_replace($this->pregArr, $data, $template )  ) ;

            return $this->_cleanTags( $res )  ;
//            echo'<pre>';print_r( $this->pregArr );echo'</pre>'.__FILE__.' '.__LINE__;
//            echo'<pre>';print_r( $res );echo'</pre>'.__FILE__.' '.__LINE__;
//            echo'<pre>';print_r( $data );echo'</pre>'.__FILE__.' '.__LINE__;
//            echo'<pre>';print_r( $template );echo'</pre>'.__FILE__.' '.__LINE__;


        }
        /**
         * Редактирования Breadcrumbs
         *
         * @since version
         */
        protected function correctBreadcrumbs(){
//            $classname

            $body = $this->app->getBody();
            $dom = new \GNZ11\Document\Dom();
            $dom->loadHTML( mb_convert_encoding( $body , 'HTML-ENTITIES', 'UTF-8' ) );
            $xpath = new \DOMXPath($dom);
            $Nodes = $xpath->query("//*[contains(@class, 'breadcrumbs')] //li//span[contains(@itemprop, 'name')]");
            foreach ( $Nodes as $node) {
                $node->nodeValue = $this->loadDataTemplate( $node->nodeValue ) ;
//                echo'<pre>';print_r( $node->nodeValue  );echo'</pre>'.__FILE__.' '.__LINE__;
            }#END FOREACH

            $body =  $dom->saveHTML() ;
            $this->app->setBody( $body ) ;
            
//            die(__FILE__ .' '. __LINE__ );




           /* $dom->loadHTML( mb_convert_encoding( $body , 'HTML-ENTITIES', 'UTF-8' ) );
            $dom->getElementsByTagName( $nodeName ) ;*/
        }

        /**
         * Получить текущию категорию
         *
         * @since version
         */
        protected function getCatrgory (){
            $CategoryTable = \JTable::getInstance('Category', 'jshop') ;
            $parentCategoryTable = clone $CategoryTable ;
            $category_id = $this->app->input->get('category_id' , false ) ;
            $CategoryTable->load( $category_id );
            return $CategoryTable ;
        }


        public function _checkCategory(  ){

            $CategoryTable = $this->getCatrgory();

            $parentCategoryTable = clone $CategoryTable ;
            $parentCategoryTable->load( $CategoryTable->category_parent_id ) ;
            // Родительская категория
            $this->ParentCategory = $parentCategoryTable->{'name_ru-RU'} ;

            $Pathway = $this->app->getPathway();
            $PathwayNamesArr = $Pathway->getPathwayNames() ;


            if ( isset( $PathwayNamesArr[1] ) ) {
                // Главная категория
                $this->RootCategory = $PathwayNamesArr[1] ;
            }#END IF

            if ( $CategoryTable->{'name_ru-RU'} != $this->RootCategory ) {
                // текушая категория
                $this->CurrentCategory = $CategoryTable->{'name_ru-RU'} ;
            }#END IF
        }

        /**
         * Кдалить мусор из тега ( h1 , title )
         * @param $tag
         *
         * @return string
         *
         * @since version
         */
        private function _cleanTags( $tag ){
            $searchArr = [
                '()' ,
                '[]' ,
            ];
            return trim( str_replace(  $searchArr ,'', $tag ) ) ;
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
            if ( $nodeName == 'description') {
                $metas = $dom->getElementsByTagName('meta');
                foreach ($metas as $meta) {
                    if (strtolower($meta->getAttribute('name')) == 'description') {
                        $meta->setAttribute("content", $content);
                    }
                }
            }else{
                $dom->getElementsByTagName( $nodeName )->item(0)->nodeValue = $content;
            }#END IF

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

            if ($nodeName == 'description' ) {
                $metas = $dom->getElementsByTagName('meta');
                foreach ($metas as $meta) {
                    if (strtolower($meta->getAttribute('name')) == 'description') {
                        return $meta->getAttribute('content');
                    }
                }
            }#END IF
            return $dom->getElementsByTagName( $nodeName )->item(0)->nodeValue ;
		}

		private function getCharacteristicsName(){
			$lang	= \JSFactory::getLang();
			$characteristics = $this->app->input->get('characteristics' , false , 'ARRAY' ) ;




            if ( !$characteristics )  return null  ; #END IF


			
			# id групп полей
			$groupId = self::_array_key_first( $characteristics );

//            echo'<pre>';print_r( $groupId );echo'</pre>'.__FILE__.' '.__LINE__;
//            echo'<pre>';print_r( $characteristics );echo'</pre>'.__FILE__.' '.__LINE__;



			$firstEl = array_shift($characteristics);
			$firstId = array_shift($firstEl);



//            die(__FILE__ .' '. __LINE__ );



			/*echo'<pre>';print_r( is_numeric( $firstId.'ip' ) );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( is_int( $firstId ) );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $firstId );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );*/

			if( !is_numeric( $firstId ) )
			{
				return  $firstId ;
			}#END IF



			if( empty( $firstId ) )
			{
				return false ;
			}#END IF

			$query 	= "SELECT
							`id` as `value`,
							`".$lang->get('name')."` as `text`
						FROM `#__jshopping_products_extra_field_values`
						WHERE `id` = ".$firstId."
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
		private function getManufactureName( $manufacturersID ){
            $manufacturer = \JTable::getInstance('manufacturer', 'jshop');
			$manufacturer->load( $manufacturersID );
            return $manufacturer->getName();
		}

        /**
         * Полифилл array_key_first (PHP 7 >= 7.3.0)
         * Получить первый ключ заданного массива array, не затрагивая внутренний указатель массива.
         *
         * @param $array array
         * @since 3.9
         * @Todo IS TEMPLATE
         */
		private static function _array_key_first( array $array ){
            foreach($array as $key => $unused) {
                return $key;
            }
            return null ;
        }

	}