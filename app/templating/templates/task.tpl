{include file="header.inc.tpl"}
	<div class="grid_8">
		<h2>{$task->getTitle()}</h2>
	
		<div class="task_content">
			
			<p class="details">
				<span class="time_since">{IO::timeSinceSqlTime($task->getCreatedTime())} ago</span>
				&middot; {Organisations::nameFromId($task->getOrganisationId())}
				{assign var="wordcount" value=$task->getWordCount()}
				{if $wordcount}
					&middot; {$wordcount|number_format} words
				{/if}
			</p>
			
			<ul class="tags">
				{if $task->areSourceAndTargetSet()}
					{Languages::languageNameFromId($task->getSourceId())} 
					to 
					{Languages::languageNameFromId($task->getTargetId())}
				{/if}
				{foreach from=$task->getTags() item=tag}
					<li>{include file="inc.tag.tpl" tag=$tag}</li>
				{/foreach}
			</ul>
		</div>

		{if isset($task_file_info)}
			{assign var="task_id" value=$task->getTaskId()}
			{if isset($user)}
				<h3>1. Download the file to translate it</h3>
				<p>
					<a href="{urlFor name="download-task" options="task_id.$task_id"}">Click here to download the file to translate it.</a>
				</p>
				<h3>2. Finished translating this file on your computer? Upload your translated file</h3>
				<form method="post" action="{urlFor name="task-upload-edited" options="task_id.$task_id"}" enctype="multipart/form-data">
					<input type="hidden" name="task_id" value="{$task->getTaskId()}">
					<fieldset>
						<p>
							<label for="edited_file">Upload translated file</label>
							<input type="file" name="edited_file" id="edited_file">
						</p>
						<p class="desc">
							Can be anything, even a .zip collection of files. Max file size {$max_file_size}MB.
						</p>  
						<input type="submit" value="Submit" name="submit">
					</fieldset> 
				</form>
				<h3>Admin section</h3>
				{if $latest_version > 0}
					<p><a href="{urlFor name="download-task-latest-version" options="task_id.$task_id"}">Download the latest translation.</a></p>
				{else}
					<p>No translated files uploaded yet. Check back here again.</p>
				{/if}
			{/if}
				
			<ul>
				{if isset($user)}
				{/if}
			</ul>

			{if isset($user)}
				
			{else}
				<p>Please <a href="{urlFor name="login"}">log in</a> to be able to accept translation jobs.</p>
			{/if}
		{/if}
	</div>
	<div id="sidebar" class="grid_4">
		<p><a href="{urlFor name="task-upload"}">+ New task</a></p>
	</div>

{include file="footer.inc.tpl"}
