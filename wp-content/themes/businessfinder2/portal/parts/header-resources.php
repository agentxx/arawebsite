{if $options->theme->header->displayHeaderResources}
	{var $resources = isset(wp_count_posts( 'ait-item' )->publish) ? wp_count_posts( 'ait-item' )->publish : 0}
	<div class="header-resources">

		<span class="resources-data">
			<span class="resources-count">{$resources}</span>
			<span class="resources-text">{__ 'Resources'}</span>
		</span>
		{if is_user_logged_in()}
		<a href="{!admin_url('post-new.php?post_type=ait-item')}" class="resources-button ait-sc-button">{__ 'Add'}</a>
		{else}
		{if function_exists('pll_get_post')}
			{var $link = get_permalink( pll_get_post( $options->theme->header->headerResourcesButtonLink ) )}
		{else}
			{var $link = get_permalink( $options->theme->header->headerResourcesButtonLink )}
		{/if}
		<a href="{!$link}" class="resources-button ait-sc-button">{__ 'Add'}</a>
		{/if}

	</div>
{/if}