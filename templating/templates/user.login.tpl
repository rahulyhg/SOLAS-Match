{include file="header.inc.tpl"}
<div class="grid_8">
	<h2>Log In</h2>
	<form method="post" action="/process/login.php">
		<fieldset>
			<p>
				<label for="email">Email</label>
				<input type="text" name="email" id="email">
			</p> 
			<p>
				<label for="password">Password</label>
				<input type="password" name="password" id="password">
			</p>
			<input type="submit" value="Log In" name="submit">
		</fieldset>
	</form>
</div>
<div id="sidebar" class="grid_4">
	
</div>
{include file="footer.inc.tpl"}
