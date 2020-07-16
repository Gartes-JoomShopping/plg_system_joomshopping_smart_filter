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
        private $h1 = '';
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

        public $pregArr = [
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
         * Массив для хранения мета данных категори Тэг h1
         * @var array
         * @since version
         */
        public $dataForCategoryH1;
        /**
         * META description
         * @var string
         * @since version
         */
        public $Description;


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



            /*echo'<pre>';print_r( '-'.$this->FilterStringParam.'-' );echo'</pre>'.__FILE__.' '.__LINE__;
            die(__FILE__ .' '. __LINE__ );*/


            $this->dataForCategoryH1 = [
                $this->RootCategory,
                $this->ParentCategory,
                $this->CurrentCategory,
                $this->h1,
                $this->City,
                $this->FilterStringParam ,
            ];

            /**
             * Обработка тэга h1
             */
            $processing_rules_h1 = $this->params->get('processing_rules_h1', 0 );
            $key = array_search('{H1}', $this->pregArr);
            $arrSearch = $this->getArrSearchForClean() ;
            foreach ($this->dataForCategoryH1 as $i => $item) {
                $this->dataForCategoryH1[$i] = str_replace( $arrSearch , '' , $item ) ;
            }#END FOREACH



            

            switch ($processing_rules_h1){
                case 1 :
                    $template_h1 = $CategoryTable->{$lang->get('name')};
                    break ;
                case 2 :
                    $template_h1 = $CategoryTable->{$lang->get('name')};
                    $pos = \GNZ11\Document\Text::strpos_array($template_h1 , $this->pregArr ) ;
                    if (!$pos) {
                        $template_h1 = $this->params->get('template_h1', false );
                    }#END IF

                    break;
                default:
                    $template_h1 = $this->params->get('template_h1', false );
            }

            $this->h1 = $this->loadDataTemplate( $template_h1 ,  $this->dataForCategoryH1 ) ;


            $this->setContentNode('h1', $this->h1 );
            $this->dataForCategoryH1[ $key ] =  $this->h1  ;



            $processing_rules_title = $this->params->get('processing_rules_title', 0 );
            switch ($processing_rules_title){
                case 1 :
                    $template_Title = $CategoryTable->{$lang->get('meta_title')};

                    break ;
                case 2 :
                    $template_Title = $CategoryTable->{$lang->get('meta_title')};
                    if (empty( $template_Title )) {
                        $template_Title = $this->params->get('template_title', false );
                    }#END IF
                    break ;
                case 3 :
                    $template_Title = $CategoryTable->{$lang->get('meta_title')};
                    $pos = \GNZ11\Document\Text::strpos_array($template_Title , $this->pregArr ) ;
                    if (!$pos) {
                        $template_Title = $this->params->get('template_title', false );
                    }#END IF
                    break ;
                default :
                    $template_Title = $this->params->get('template_title', false );
            }
            $this->Title = $this->loadDataTemplate( $template_Title ,  $this->dataForCategoryH1 ) ;
            $this->setContentNode('title', $this->Title );



            $processing_rules_description = $this->params->get('processing_rules_description', 0 );
            switch ($processing_rules_description){
                case 1 :
                    $template_description = $CategoryTable->{$lang->get('meta_description')} ;
                    $this->Description = $this->loadDataTemplate( $template_description ,  $this->dataForCategoryH1 ) ;
                    $this->setContentNode('description', $this->Description );
                    break ;
                case 2 :
                    $template_description = $CategoryTable->{$lang->get('meta_description')} ;
                    if (empty( $template_description ) ) {
                        $template_description = $this->params->get('template_description', false );
                    }#END IF
                    $this->Description = $this->loadDataTemplate( $template_description ,  $this->dataForCategoryH1 ) ;
                    $this->setContentNode('description', $this->Description );
                    break ;
                case 3 :
                    $template_description = $CategoryTable->{$lang->get('meta_description')} ;
                    $pos = \GNZ11\Document\Text::strpos_array($template_description , $this->pregArr) ;
                    if (!$pos) {
                        $template_description = $this->params->get('template_description', false );
                    }#END IF
                    $this->Description = $this->loadDataTemplate( $template_description ,  $this->dataForCategoryH1 ) ;
                    $this->setContentNode('description', $this->Description );
                    break ;
                default :
                    $template_description = $this->params->get('template_description', false );
                    $this->Description = $this->loadDataTemplate( $template_description ,  $this->dataForCategoryH1 ) ;
                    $this->setContentNode('description', $this->Description );
            }


            if ( $this->params->get('process_breadcrumbs', 0 ) ) {
                $this->correctBreadcrumbs();
            }#END IF

            $this->cleanCode();
        }

        /**
         * Удалить неиспользованные шаблоны
         *
         * @since version
         */
        public function cleanCode(){
		    $arrSearch = $this->getArrSearchForClean() ;
            # После выполнения всех операций удаляем все шаблоны в ненужных местах
            $body = $this->app->getBody();

            $body = str_replace( $arrSearch , '' , $body ) ;
//            $body = $this->_cleanTags( $body );
            $this->app->setBody( $body ) ;
        }

        /**
         * Создать массив для очищения шаблонов
         * @return array
         *
         * @since version
         */
        protected function getArrSearchForClean(){
            $arrSearch = [];
            foreach ( $this->pregArr as $item) {
                $arrSearch[] = '('.$item.')' ;
                $arrSearch[] = '['.$item.']' ;
                $arrSearch[] = $item ;
            }#END FOREACH
            return $arrSearch ;
        }

        /**
         * Замена шаблонов в строке
         * @param $template
         *
         * @param bool $data
         * @return string
         *
         * @since version
         */
        protected function loadDataTemplate( $template , $data = false ){

            $template = str_replace('[[[CITY]]]' , '{City}' , $template ) ;
            if (!$data) {
                $data = [
                    $this->RootCategory,
                    $this->ParentCategory,
                    $this->CurrentCategory,
                    $this->h1,
                    $this->City,
                    $this->FilterStringParam ,
                ];
            }#END IF
            $res = trim( str_replace( $this->pregArr , $data, $template )  ) ;
            # удалить повторяющиеся пробелы в тексте
            $res =  preg_replace('/ {2,}/',' ',$res);


            return $this->_cleanTags( $res )  ;
        }
        
        /**
         * Редактирования Breadcrumbs
         *
         * @since version
         */
        public function correctBreadcrumbs(){
//            $classname

            $body = $this->app->getBody();
            $dom = new \GNZ11\Document\Dom();
            $dom->loadHTML( mb_convert_encoding( $body , 'HTML-ENTITIES', 'UTF-8' ) );
            $xpath = new \DOMXPath($dom);
            $Nodes = $xpath->query("//*[contains(@class, 'breadcrumbs')] //li//span[contains(@itemprop, 'name')]");
            


            foreach ( $Nodes as $node) {
                $node->nodeValue = $this->loadDataTemplate( $node->nodeValue ) ;
 
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
            }else{
                $this->CurrentCategory = $this->RootCategory ;
            }#END IF

        }

        /**
         * Удалить мусор из тега ( h1 , title )
         * @param $tag
         *
         * @return string
         *
         * @since version
         */
        public function _cleanTags( $tag ){
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
//			$dom->loadHTML( mb_convert_encoding( $body , 'HTML-ENTITIES', 'UTF-8' ) );
			$dom->loadHTML(   $body   );
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