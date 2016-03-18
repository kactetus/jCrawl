<?php require __DIR__.'/vendor/autoload.php';?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>jCrawl - PHP Web Crawler</title>
<meta name="description" content="">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://necolas.github.io/normalize.css/3.0.3/normalize.css">
<style>
	body, html, * {
		padding: 0;
		margin: 0;
		font-family: "Helvetica Neue";
		font-weight: 400;
		-webkit-font-smoothing: antialiased;
    	-moz-osx-font-smoothing: grayscale;
	}
	body {
		padding-top: 60px;
		color: #333;
	}
	h1, h2, h3, h4, h5, h6 {
		font-weight: 300;
	}
	nav {
		background: rgb(51, 51, 51);
		height: 45px;
		position: fixed;
		width: 100%;
		top: 0;
	}

	a {
		text-decoration: none;
		color: #08c;
	}

	strong {
		font-weight: bold;
		color: #000;
	}

	.nav-container {
		width: 960px;
		margin: 0 auto;
		overflow: hidden;
	}

	 .container {
	 	width: 960px;
	 	margin: 0 auto;
	 }

	.logo, .menu, .search  {
		float: left;
		position: relative;
		display: inline-block;
	}
	
	.logo {
		width: 10%;
	}

	.logo > a {
		color: #fff;
		padding: 12px;
		display: inline-block;
	}

	.menu {
		width: 40%;
		list-style: none;
		padding: 0;
		margin: 0;
		text-align: right;
	}
	
	.menu li {
		display: inline-block;
	}

	.menu > li > a {
		color: #fff;
		padding: 12px;
		display: inline-block;
	}

	.search {
		width: 50%;
	}

	.form-control {
		width: 100%;
		height: 15px;
		display: inline-block;
		padding: 5px;
		border: 1px solid rgb(230, 230, 230);
		font-size: 1.1em;
	}

	.result-info {
		margin: 25px 0;
	}

	.result {
		width: 60%;
		margin-bottom: 25px;
		overflow: hidden;
	}
	.title {
		color: #000;
		display: block;
		font-size: 1.3em;
	}
	.url {
		display: block;
		margin-bottom: 5px;
		font-size: 0.9em;
	}
	button {
		background: rgb(247, 247, 247);
		border: none;
		padding: 10px 25px;
		margin: 10px 0;
	}
	button:hover {
		cursor: pointer;
	}

	.alert {
		margin: 20px 0;
		background: rgb(247, 247, 247);
		padding: 25px;
	}
	.top-form {
		background: #666;
		border-color: #777;
		padding: 8px;
		margin-top: 6px;
	}
	.top-form:focus {
		background: #fff;
	}
</style>

<!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
</head>
<body>
<!--[if lt IE 7]>
    <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
<![endif]-->

<?php include __DIR__.'/header.php'; ?>

<div class="container">
<?php if(isset($_GET['q']) && !isset($_GET['p'])): ?>

	<?php 
	$query = '%'.$_GET['q'].'%';
	$query = str_replace(' ', '%', $query);
	$sites = \Crawler\Sites::where('description', 'like', $query)
							->orWhere('title', 'like', $query)
							->orWhere('html', 'like', $query)
							->get(); 
	?>
	
	<div class="result-info">
		<h1> Searching for "<?php echo $_GET['q']; ?>"</h1>
		About <?php echo number_format($sites->count()); ?> results...
	</div>

	<?php foreach($sites as $site): $site->title = strip_tags($site->title); $site->description = strip_tags($site->description);?>
		<div class="result">
			<a href="<?php echo $site->url; ?>" target="_blank" class="title"><?php echo truncate(highlight($site->title, $query), 50); ?></a>
			<a href="<?php echo $site->url; ?>" target="_blank" class="url"><?php echo shorturl($site->url); ?></a>
			<p class="description"><?php echo highlight($site->description, $query); ?></p>
		</div>
	<?php endforeach; ?>


<?php endif; ?>
</div>

<?php if(isset($_GET['p']) && $_GET['p'] == 'admin'): ?>

	<div class="container">
		<?php if(isset($_GET['site']) && $_GET['site'] == 'added'): ?>
			<div class="alert">
				<strong>Success!</strong> Your site has been added and is currently being crawled.
			</div>
		<?php endif; ?>
		<div class="add-sites" style="width: 30%; display: inline-block; float: left; clear: left; position: relative;">
			<h1>Add Websites</h1>
			<form action="/add-site.php" method="post">
				<input type="text" class="form-control"  name="site" style="width: 100% !important;">
				<button type="submit">Add Website</button>
			</form>
			<hr>
			<form action="/add-site.php?indexAll=true" method="post">
				<button type="submit">Follow Links</button>
			</form>
			<hr>
			<form action="/add-site.php?drop-db=true" method="post">
				<button type="submit">Clear Database</button>
			</form>
		</div>
		
	</div>

<?php endif;  ?>	


<?php include __DIR__.'/footer.php'; ?>









