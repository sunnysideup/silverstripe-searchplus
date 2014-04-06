<% if RecommendedSearchPlusSection %>
<div id="RecommendedSearchPlusSections">
	<% with/loop RecommendedSearchPlusSection %>
	<div class="recommendedSearchPlusSectionOne">
		<h3>$Title</h3>
		<p>$Intro</p>
		<% if ParentPage %><% with/loop ParentPage %><ul><% with/loop Children %><li class="$FirstLast $EvenOdd item"><a href="$Link">$Title</a></li><% end_with/loop %></ul><% end_with/loop %><% end_if %>
	</div>
	<% end_with/loop %>
</div>
<% end_if %>