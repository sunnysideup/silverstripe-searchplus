<% if Recommendations %>
<div id="SearchPlusRecommendations">
	<h3>Popular Pages for '$Query' search phrase</h3>
	<ul><% with/loop Recommendations %><li><a href="$Link">$Title</a></li><% end_with/loop %></ul>
</div>
<% end_if %>