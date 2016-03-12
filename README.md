# jCrawl
jCrawl is a web crawler that can be used for all sorts of purposes and can be used in various different applications. It is currently set up as a cli script for use on the command line. 

jCrawl currently does not save any information that it crawls to a database at this time. You can easily connect it to a database of your choosing. I plan on setting up a full featured version that can be demo'ed in the browser.

Current versions of jCrawl do not adhere to follow or nofollow directives, but this will be implemented in later versions, as it important to respect these settings. jCrawl also does not currently possess the ability to crawl files such as css or js files. If users desire this feature then it can be added in at a later time.



## Installation
Update your composer.json file to include the following require

```
{
	"require": {
		"jeremyam/jcrawl": "0.6.x-dev"
	}
}
```

## Usage
Make sure you are in the root of the application. And then type the following command

```
php crawl http://www.example.com
```

Making sure to change "http://www.example.com" to the desired domain name that you want to crawl.