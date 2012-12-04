<% if Recommendations %>
<div id="SearchPlusRecommendations">
	<h3>Popular Pages for '$Query' search phrase</h3>
	<ul><% control Recommendations %><li><a href="$Link">$Title</a></li><% end_control %></ul>
</div>
<% end_if %>