<h2 id="SearchPlusSearchResultListHeading">Search for <q>$Query</q> : $Results.TotalItems results </h2>
<% include SearchPlusMoreThanOnePage %>
<% if Results %>
<ul id="SearchResults">
<% control Results %>
	<li class="$EvenOdd $FirstLast <% if IsRecommended %>recommended<% end_if %>">
		<h3><a href="$Link"><% if MenuTitle %>$HighlightedTitle<% end_if %></a></h3>
		<p>$Content.ContextSummary(300) ...</p>
		<a href="$Link" title="Read more about $Title.ATT">Read more &gt;&gt;</a>
	</li>
<% end_control %>
</ul>
<% else %>
<p class="SearchPlusSearchResultListRegret">Sorry, no pages matched your search, please try again.</p>
<% end_if %>
<% include SearchPlusMoreThanOnePage %>
