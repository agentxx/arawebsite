{block content}
{? global $wp_query}
{var $query = $wp_query}
{var $taxType = isset($_REQUEST['item-tax']) && $_REQUEST['item-tax'] != "" ? $_REQUEST['item-tax'] : 'ait-items'}

{if $query->have_posts()}
	
	{if $taxType === 'ait-events-pro'}
		{includePart portal/parts/search-filters, postType => 'ait-event-pro', taxonomy => "ait-events-pro", current => $query->post_count, max => $query->found_posts}
	{else}
		{includePart portal/parts/search-filters, current => $query->post_count, max => $query->found_posts}
	{/if}

	{if defined("AIT_ADVANCED_FILTERS_ENABLED")}
		{includePart portal/parts/advanced-filters, query => $query}
	{/if}

	{*includePart parts/pagination, location => pagination-above, max => $query->max_num_pages*}

	{if isset($_REQUEST['a']) && $_REQUEST['a'] != ""}
		{var $isAdvanced = true}
		{var $layout = $options->theme->items->searchLayout}
		{var $numOfColumns = ($layout == 'box' ? '4' : '1')}
		
		{if $taxType === 'ait-events-pro'}
			<div class="events-container elm-item-organizer">
				<div class="elm-item-organizer-container carousel-disabled layout-list column-1">
		{else}
			<div class="items-container elm-item-organizer{if $layout == 'box'} organizer-alt{/if}">
				<div class="elm-item-organizer-container carousel-disabled layout-{$layout} column-{$numOfColumns}">
		{/if}
			
				<div class="content">
						{customLoop from $query as $post}
							{if $taxType === 'ait-events-pro'}
								{includePart portal/parts/event-container}
							{else}
								{includePart parts/post-content}
							{/if}
						{/customLoop}
				</div>
			</div>
		</div>
	{else}
		<div class="items-container">
			
			{customLoop from $query as $post}
				{if $taxType === 'ait-events-pro'}
					{includePart portal/parts/event-container}
				{else}
					{includePart parts/post-content}
				{/if}
			{/customLoop}
			
		</div>
	{/if}

	{includePart parts/pagination, location => pagination-below, max => $query->max_num_pages}

{else}
	{includePart parts/none, message => nothing-found}
{/if}

