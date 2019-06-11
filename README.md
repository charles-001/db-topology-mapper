# Database Topology Mapper

![DB Topology Mapper](https://i.imgur.com/EMP7C5e.png)

```Database Topology Mapper``` is a web tool that pairs master & slave servers into a beautiful map using D3.js that is easily digestible for DBAs and anyone else in your organization.

# Installation
I run this on Apache with PHP 7.2.10, but it should be able to run on whatever you want. To set this up, simply put all the files on your HTTP server and give it a whirl!

# Key things to change to fit your infrastructure
* ```$environments``` variable is to separate servers into their own branch. To create an environment, simply add it to the array at the top of ```create_topology.php```. You can set the colors of each environment to your liking in the array as well.
* Getting servers into the ```$servers``` array is very dependant on how you want to feed it. You can read from a file, put the servers inline, use Nagios' database (suggested) if you have that setup, or your own custom method if you want to get advanced. Each server needs to have these properties: ip, version, master, and role with the server name as the key. By default, I set up the example using a file which is called ```server_list.json```
* If you'd like to change the appearance of the bubbles, check out ```style.css```. You can set them however you like.

Outside of those things, you shouldn't need to change anything else! If you have any questions or issues, feel free to reach out to me.
