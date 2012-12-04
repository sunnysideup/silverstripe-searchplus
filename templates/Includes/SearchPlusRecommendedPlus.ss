<% if RecommendedSearchPlusSection %>
<div id="RecommendedSearchPlusSections">
	<% control RecommendedSearchPlusSection %>
	<div class="recommendedSearchPlusSectionOne">
		<h3>$Title</h3>
		<p>$Intro</p>
		<% if ParentPage %><% control ParentPage %><ul><% control Children %><li class="$FirstLast $EvenOdd item"><a href="$Link">$Title</a></li><% end_control %></ul><% end_control %><% end_if %>
	</div>
	<% end_control %>
</div>
<% end_if %>