<p>Hello {{user.name}}!</p>
<nav>
{{partial navView}}
</nav>

<div id="myself">
{{partial user userInfo}}
</div>

{{ifpartial user.isAdmin adminViews}}

<table>
{{for user.inbox.items view partials.itemListView}}
</table>