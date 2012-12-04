<% if Results.MoreThanOnePage %>
<p class="searchPlusMoreThanOnePageNumbers">
	<span class="searchPlusMoreThanOnePageSummary">Page $Results.CurrentPage of $Results.TotalPages</span>
 	<span class="prevNextNumbers">
		<% if Results.NotFirstPage %><a class="prev" href="$Results.PrevLink" title="View the previous page">Prev</a> | <% end_if %>
		<span class="numbers"><% control Results.Pages %><% if CurrentBool %><span class="currentNumber">$PageNum</span><% else %><a href="$Link" title="View page number $PageNum" class="notCurrentNumber">$PageNum</a><% end_if %><% end_control %></span>
	<% if Results.NotLastPage %> | <a class="next" href="$Results.NextLink" title="View the next page">Next</a><% end_if %>
	</span>
</p>
<% end_if %>