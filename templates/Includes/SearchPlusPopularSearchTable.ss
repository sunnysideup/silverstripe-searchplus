<div id="SearchPlusPopularSearches">
	<h3>Top $Limit Search Phrases during the last $Days days</h3>
	<h4>By Popularity (click on title to customise search phrase results)</h4>
	<table summary="most popular search phrases by count" class="graphTable">
		<tr>
			<% control DataByCount %>
			<td class="title">
				<a href="admin/searchplus/SearchHistory/{$ParentID}/edit">$Title</a>
			</td>
			<td class="background"><div style="width: {$Width}%;" class="foreground">$Count</div></td></tr>
			<% end_control %>
		</tr>
	</table>
	<h4>By Name</h4>
	<table summary="most popular search phrases by title" class="graphTable">
		<tr>
			<% control DataByTitle %>
			<td class="title">$Title</td>
			<td class="background"><div style="width: {$Width}%;" class="foreground">$Count</div></td></tr>
			<% end_control %>
		</tr>
	</table>
</div>