<% if PopularSearchWordsForAllUsers %>
<div id="SearchPlusPopular">
	<h3>Popular searches</h3>
	<ul><% control PopularSearchWordsForAllUsers %><li><a href="$Link">$Title</a></li><% end_control %></ul>
</div>
<% end_if %>