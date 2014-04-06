<% if RecommendedSearchPlusSection %>
<div id="RecommendedSearchPlusSections">
	<% loop RecommendedSearchPlusSection %>
	<div class="recommendedSearchPlusSectionOne">
		<h3>$Title</h3>
		<p>$Intro</p>
		<% if ParentPage %><% with ParentPage %><ul><% loop Children %><li class="$FirstLast $EvenOdd item"><a href="$Link">$Title</a></li><% end_loop %></ul><% end_with %><% end_if %>
	</div>
	<% end_loop %>
</div>
<% end_if %>
