<% if PopularSearchWordsForAllUsers %>
<div id="SearchPlusPopular">
	<h3>Popular searches</h3>
	<ul><% with/loop PopularSearchWordsForAllUsers %><li><a href="$Link">$Title</a></li><% end_with/loop %></ul>
</div>
<% end_if %>