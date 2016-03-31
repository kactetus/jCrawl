
<nav class="nav">
	<div class="nav-container">
		<div class="logo">
			<a href="/">jCrawl</a>
		</div>
		<div class="search">
			<form action="index.php" method="get">
				<input type="text" name="q" class="form-control top-form" placeholder="Search the web..." value="<?php if(isset($_GET['q'])) echo $_GET['q']; ?>">
			</form>
		</div>
		<ul class="menu">
			<li><a href="/?p=admin">Admin</a></li>
		</ul>
	</div>
</nav>