{include file="header.inc.tpl"}
	<div class="grid_8">
		{if isset($tasks)}
			<h2 class="section_top">Translation Tasks</h2>
			{foreach from=$tasks item=task name=tasks_loop}
				<div class="task">
					<h3><a href="{$task->url()}">{$task->title()}</a></h3>
					<p class="details">
						<span class="time_since">{$s->io->timeSince($task->createdTime())} ago</span> <a href="">{$task->organisation()}</a>
					</p>
					{assign var="tag_ids" value=$task->tagIDs()}
					{if $tag_ids}
						<ul class="tags">
							{foreach from=$tag_ids item=tag_id}
								<li>{$s->tags->tagHTML($tag_id)}</a>
							{/foreach}
						</ul>
					{/if}
				</div>
			{/foreach}
		{/if}
<!--
		<div class="task">
			<h3><a href="#">Our Mission. Relieve poverty, support healthcar…</a></h3>
			<p class="details">
				<span class="time_since">1 day ago</span> <a href="#">PeopleOrg</a>
			</p>
			<ul class="tags">
				<li><a class="tag" href="tag-to-russian.html"><span class="label">To Russian</span></a></li>
				<li><a class="tag" href="tag-to-russian.html"><span class="label">From English</span></a></li>
				<li><a class="tag" href="tag-to-russian.html"><span class="label">review</span></a></li>
				<li><a class="tag" href="tag-to-russian.html"><span class="label">special olympics</span></a></li>
				<li><a class="tag" href="tag-to-russian.html"><span class="label">informal</span></a></li>
			</ul>
		</div>

		<div class="task">
			<h3><a href="#">Our Mission. Relieve poverty, support healthcar…</a></h3>
			<p class="details">
				<span class="time_since">1 day ago</span> <a href="#">PeopleOrg</a>
			</p>
			<ul class="tags">
				<li><a class="tag" href="tag-to-russian.html">To</a></li>
				<li><a class="tag" href="tag-to-russian.html">From</a></li>
				<li><a class="tag" href="tag-to-russian.html"></a></li>
			</ul>
		</div>
		
		<div class="task">
			<h3><a href="#">We are a registered charity, and a large enviro…</a></h3>
			<p class="details">
				<span class="time_since">1 day ago</span> <a href="#">TransOrg</a>
			</p>
			<ul class="tags">
				<li><a class="tag" href="tag-to-russian.html">To Hindi</a></li>
				<li><a class="tag" href="tag-to-russian.html">From English</a></li>
				<li><a class="tag" href="tag-to-russian.html">translate</a></li>
				<li><a class="tag" href="tag-to-russian.html">inhouse</a></li>
			</ul>
		</div>
		
		<div class="task">
			<h3><a href="#">Qu'il s'agisse d'accueillir des populations…</a></h3>
			<p class="details">
				<span class="time_since">2 days ago</span> <a href="#">MedOrg</a>
			</p>
			<ul class="tags">
				<li><a class="tag" href="tag-to-russian.html">To English</a></li>
				<li><a class="tag" href="tag-to-russian.html">From French</a></li>
				<li><a class="tag" href="tag-to-russian.html">translate</a></li>
				<li><a class="tag" href="tag-to-russian.html">medical</a></li>
			</ul>
		</div>
		
		<div class="task">
			<h3><a href="#">Sign up to become a translator, project manag…</a></h3>
			<p class="details">
				<span class="time_since">3 day ago</span> <a href="#">PeopleOrg</a>
			</p>
			<ul class="tags">
				<li><a class="tag" href="tag-to-russian.html">To Russian</a></li>
				<li><a class="tag" href="tag-to-russian.html">From English</a></li>
				<li><a class="tag" href="tag-to-russian.html">special olympics</a></li>
				<li><a class="tag" href="tag-to-russian.html">translate</a></li>
				<li><a class="tag" href="tag-to-russian.html">informal</a></li>
			</ul>
		</div>

-->
	</div>
	<div id="sidebar" class="grid_4">
		<p><a href="/mockup/pm.create-task.php">+ New task</a></p>
		{if $top_tags}
			<ul class="tags">
				{foreach from=$top_tags item=tag_freq}
					<li>{$s->tags->tagHTML($tag_freq.tag_id)}
					{if $tag_freq.frequency > 1}
					 x {$tag_freq.frequency}
					{/if}
					</li>
				{/foreach}
			</ul>
		{/if}
	</div>
{include file="footer.inc.tpl"}
