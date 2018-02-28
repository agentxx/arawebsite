<?php


class AitEventCpt extends AitPublicCpt
{

	/**
	 * @param $id
	 * @param $config
	 * @param $paths
	 */
	public function __construct($id, $config, $paths)
	{
		parent::__construct($id, $config, $paths);
	}



	public static function saveEventMeta($postId, $post, $metabox, $data)
	{
		if($post->post_type == 'ait-event'){

			if(isset($data['dateFrom'])){
				update_post_meta($postId, 'event_date_from', $data['dateFrom']);
			}

			update_post_meta($postId, '_ait-event_event-data', $data);
		}
	}



}
