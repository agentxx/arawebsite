<?php

class AitCsvImportExportHelpers
{

    public static function getRawConfig($internalId)
    {
        $obj = aitManager('cpts')->getByInternalId($internalId);
        $metaboxes = $obj->getMetaboxes();

        // strip "ait-" from internalId and add "-data"
        $metaId = substr($internalId, 4) . "-data";
        if (isset($metaboxes[$metaId])) {
            return $metaboxes[$metaId]->getRawConfig();
        }

        // if metabox doesn't have sufix -data
        $metaId = substr($internalId, 4);
        if (isset($metaboxes[$metaId])) {
            return $metaboxes[$metaId]->getRawConfig();
        }

    }



    public static function getMetaKey($internalId)
    {
        $obj = aitManager('cpts')->getByInternalId($internalId);
        $metaboxes = $obj->getMetaboxes();

        foreach ($metaboxes as $metabox) {
            // if metabox doesn't have sufix -data and is equal to cpt internalId
            // $k = $internalId . '-' . substr($internalId, 4);
            // var_dump($metabox->getId());
            // var_dump($k);
            if ($metabox->getId() == substr($internalId, 4)) {
                return $metabox->getPostMetaKey();
            }

            // if metabox does! have sufix -data
            if (strpos($metabox->getId(),'-data') !== false || strpos($metabox->getId(),'-option') !== false) {
                return $metabox->getPostMetaKey();
            }

        }
    }


    public static function getDefaultMeta($internalId)
    {
        $obj = aitManager('cpts')->getByInternalId($internalId);
        $metaboxes = $obj->getMetaboxes();
        $postMetaKey = self::getMetaKey($internalId);
        // var_dump(self::getMetaKey($internalId));
        // exit();

        foreach ($metaboxes as $metabox) {
                // var_dump($metabox);
            if ($metabox->getPostMetaKey() == $postMetaKey) {
                $defaults = $metabox->getConfigDefaults();
                return $defaults[$postMetaKey];
            }

            // if (strpos($metabox->getId(),'-data') !== false || strpos($key->getId(),'-option') !== false) {
            //     $defaults = $metabox->getConfigDefaults();
            //     return $defaults[self::getMetaKey($internalId)];
            // }
        }
    }


    public static function getPostMetaFields($config)
    {
        $all = array();
        $replacedFields = array();
        $parents = array();
        foreach ($config as $key => $value) {
            // ignore sections
            if (aitConfig()->isOptionsSection($value)) {
                continue;
            }

            // ignore some config options
            // if input type is special replace original column with new csv fields
            if (self::isIgnoredField($key)) {
                continue;
            } elseif (self::isException($value['type'])) {
                $newFields = self::handleInputException($key, $value);
                $replacedFields[$key] = array();
                foreach ($newFields as $newFieldKey => $newField) {
                    $parents[$newFieldKey] = array(
                        'metakey' => $key,
                        'type' => $value['type'],
                    );
                    array_push($replacedFields[$key], $newFieldKey);
                }

                $all = array_merge($all, $newFields);
            } else {
                $all[$key] = array(
                    'type'    => $value['type'],
                    'label'     =>$value['label']
                );
            }
        }
        return array( 'replacedFields' => $replacedFields, 'newFields' => $all, 'parents' => $parents);
    }







    public static function getDefaultPostFields()
    {
        return array(
            'post_name' => array(
                'type'  => 'slug',
                'label' => 'Slug'
            ),
            'post_title' => array(
                'type'  => 'text',
                'label' => 'Title'
            ),
            'post_status' => array(
                'type'  => 'post-status',
                'label' => 'Status'
            ),
            'post_content' => array(
                'type'  => 'text',
                'label' => 'Content'
            ),
            'post_excerpt' => array(
                'type'  => 'text',
                'label' => 'Excerpt'
            ),
            'post_author' => array(
                'type'  => 'author-id',
                'label' => 'Author'
            ),
            'post_parent' => array(
                'type'  => 'slug',
                'label' => 'Parent'
            ),
            'post_date' => array(
                'type'  => 'date',
                'label' => 'Date'
            ),
            'comment_status' => array(
                'type'  => 'open-closed',
                'label' => 'Comment Status'
            ),
            'ping_status' => array(
                'type'  => 'open-closed',
                'label' => 'Ping Status'
            ),
            'post_image' => array(
                'type'  => 'featured-image',
                'label' => 'Featured Image'
            ),
            // 'lang' => array(
            //     'type'  => 'language',
            //     'label' => 'Language'
            // ),
        );
    }


    public static function handleInputException($key, $value)
    {
        // $replaceField = array();
        switch ($value['type']) {
            case 'map':
                return array(
                    'address'    => array(
                        'type'    => 'text',
                        'label'     => 'Address'
                    ),
                    'latitude'    => array(
                        'type'    => 'latitude',
                        'label'     => 'Latitude'
                    ),
                    'longitude'    => array(
                        'type'    => 'longitude',
                        'label'     => 'Longitude'
                    ),
                    'streetview'    => array(
                        'type'    => 'on-off',
                        'label'     => 'Streetview'
                    ),
                );
                break;
            case 'clone':
                $clones = array();
                foreach ($value['items'] as $itemKey => $itemValue) {
                    $clones[$key.'@'.$itemKey] = array(
                        'type'    => $itemValue['type'] . ' (clone)',
                        'label'   => $itemValue['label'],
                        'metakey' => $key,
                    );
                }
                return $clones;
            default:
                return array(
                    $key => array(
                        'type'  => $value['type'],
                        'label' => $value['label'],
                    ),
                );
                break;
        }
    }


    public static function buildMetaFromCSVException($row, $type, $key, $replacedFields)
    {
        switch ($type) {
            case 'map':
                return array(
                    'address'    => $row['address'],
                    'latitude'   => empty($row['latitude']) ? 1 : $row['latitude'],
                    'longitude'  => empty($row['longitude']) ? 1 : $row['longitude'],
                    'streetview' => $row['streetview'],
                );
                break;
             case 'clone':
                $result = array();
                $items = array();

                // get first item value and find out how many items will be created
                $itemValues = explode('|', $row[$replacedFields[0]]);
                for ($i=0; $i < sizeof( $itemValues ); $i++) {

                    // build one cloneable item
                    $item = array();
                    $emptyItem = true;
                    foreach ($replacedFields as $replacedKey) {
                        $itemKey = explode('@', $replacedKey);
                        $itemKey = $itemKey[1];
                        $itemKeyValues = explode('|', $row[$replacedKey]);
                        if ($itemKeyValues[$i] != "") {
                            $emptyItem = false;
                        }
                        $item[$itemKey] = $itemKeyValues[$i];
                    }
                    if (!$emptyItem) {
                        array_push($items, $item);
                    }
                }
                return $items;
                break;
            default:
                return array();
                break;
        }
    }



    public static function getValueFromMeta($meta, $parent, $key)
    {
        switch ($parent['type']) {
            case 'map':
                return $meta[$key];
                break;
            case 'clone':
                if (empty($meta)) {
                    return '';
                }
                $return = array();
                $itemKey = explode('@', $key);
                foreach ($meta as $items) {
                    array_push($return, $items[$itemKey[1]]);
                }
                return implode('|', $return);
                break;
            default:
                # code...
                break;
        }
    }



    public static function isException($inputType)
    {
        $inputTypes = array('map', 'clone');
        return in_array($inputType, $inputTypes);
    }



    public static function isIgnoredField($field)
    {
        $ignoredFields = array('customFields', 'headerType', 'headerImageAlign');
        return in_array($field, $ignoredFields);
    }


    public static function getSupportedCpts()
    {
        return array(
            'ait-item' => array(
                'slug'  => 'ait-item',
                'type'  => 'cpt',
                'label' => 'Items',
            ),
            // 'ait-member' => array(
            //     'slug'  => 'ait-member',
            //     'type'  => 'cpt',
            //     'label' => 'Members',
            // ),
        );
    }



    public static function getPostTaxonomyFields($internalId)
    {
        $taxonomy_objects = get_object_taxonomies( $internalId, 'objects' );
        $fields = array();
        foreach ($taxonomy_objects as $slug => $taxonomy) {
            if (self::isSupportedTax($slug)) {
                $fields[$slug] = array(
                    'slug' => $slug,
                    'type'  => 'taxonomy',
                    'label' => get_taxonomy_labels( $taxonomy )->name,
                );
            }
        }
        return $fields;
    }


    public static function isSupportedTax($tax)
    {
        $supportedTaxonomies = array('ait-items', 'ait-locations');
        return in_array($tax, $supportedTaxonomies);
    }



    public static function getTaxonomyFields($taxonomy)
    {
        return array(
            'slug' => array(
                'type'  => 'slug',
                'label' => 'Slug'
            ),
            'name' => array(
                'type'  => 'text',
                'label' => 'Name'
            ),
            'description' => array(
                'type'  => 'text',
                'label' => 'Description'
            ),
            'parent' => array(
                'type'  => 'slug',
                'label' => 'Parent'
            ),
            // 'lang' => array(
            //     'type'  => 'language',
            //     'label' => 'Language'
            // ),
        );
    }


	public static function availableTaxMeta($keys)
	{
		$fields = array(
			'keywords' => array(
                'type'  => 'text',
                'label' => 'Keywords'
            ),
            'icon' => array(
                'type'  => 'image',
                'label' => 'Icon'
            ),
			'icon_color' => array(
				'type'  => 'color',
				'label' => 'Icon Color'
			),
            'map_icon' => array(
                'type'  => 'image',
                'label' => 'Icon in Map'
            ),
            'header_type' => array(
                'type'  => 'select',
                'label' => 'Header Type'
            ),
            'header_image' => array(
                'type'  => 'image',
                'label' => 'Header Image'
            ),
			'header_image_align' => array(
				'type'  => 'select',
				'label' => 'Header Image Align'
			),
			'category_featured' => array(
				'type'  => 'on-off',
				'label' => 'Featured Category'
			),
			'header_height' => array(
				'type'  => 'number',
				'label' => 'Header Height'
			),
			'taxonomy_image' => array(
				'type'  => 'image',
				'label' => 'Taxonomy Image'
			),
		);
		return array_intersect_key($fields, array_combine($keys,$keys));
	}


	public static function getTaxonomyMetaFields($taxonomy)
    {
        $theme = self::getThemeName();
		if ($taxonomy == 'ait-locations') {
			switch ($theme) {
				case 'eventguide':
					return self::availableTaxMeta(array('keywords', 'icon', 'header_type', 'header_image', 'header_image_align'));
					break;
				case 'directory2':
					return self::availableTaxMeta(array('keywords', 'icon', 'header_type', 'header_image',
						'header_image_align', 'category_featured', 'taxonomy_image', 'header_height'));
					break;
				case 'businessfinder2':
					return self::availableTaxMeta(array('keywords', 'icon', 'header_type', 'header_image',
						'header_image_align', 'category_featured', 'taxonomy_image', 'header_height'));
					break;
				case 'foodguide':
					return self::availableTaxMeta(array('keywords', 'icon', 'header_type', 'header_image',
						'header_image_align'));
					break;
				case 'cityguide':
					return self::availableTaxMeta(array('keywords', 'icon', 'header_type', 'header_image',
						'header_image_align'));
					break;
				default:
					break;
			}
		} elseif ($taxonomy == 'ait-items') {
			switch ($theme) {
				case 'eventguide':
					return self::availableTaxMeta(array('keywords', 'icon', 'icon_color', 'map_icon', 'header_type', 'header_image'));
					break;
				case 'directory2':
					return self::availableTaxMeta(array('keywords', 'icon', 'map_icon', 'header_type', 'header_image',
						'header_image_align', 'category_featured', 'header_height'));
					break;
				case 'businessfinder2':
					return self::availableTaxMeta(array('keywords', 'icon', 'map_icon', 'header_type', 'header_image',
						'header_image_align', 'category_featured', 'header_height'));
					break;
				case 'foodguide':
					return self::availableTaxMeta(array('keywords', 'icon', 'map_icon', 'header_type', 'header_image',
						'header_image_align', 'category_featured'));
					break;
				case 'cityguide':
					return self::availableTaxMeta(array('keywords', 'icon', 'map_icon', 'header_type', 'header_image',
						'header_image_align', 'category_featured'));
					break;
				default:
					break;
			}
		}
		return array();
    }



    public static function getTermsRecursivelly($taxonomy, $args = array()){
        global $wpdb;
        $result = array();
        $terms = get_terms( $taxonomy, $args );
        if(is_array($terms) && !empty($terms)){
            foreach($terms as $index => $value){
                array_push($result, $value);
                $args['parent'] = $value->term_id;
                $result = array_merge($result, self::getTermsRecursivelly($taxonomy, $args));
            }
        }

        return $result;
    }


    public static function getThemeName()
    {
        $theme = sanitize_key(get_stylesheet());
		$theme = str_replace("-child", "", $theme);
        return $theme;
    }


}
