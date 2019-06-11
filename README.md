# Database Topology Mapper

![DB Topology Mapper](https://i.imgur.com/3UvPJto.png)

```Database Topology Mapper``` is a tool that pairs master & slave servers into a beautiful map using D3.js that is easily digestible for DBAs and anyone else in your organization.

# Key things to change to fit your infrastructure
* ```$environments``` array is to separate servers into their own branch. To create an environment, simply add it to the variable at the top of ```create_topology.php```. You can set the colors of each environment to your liking in the array as well.
* Getting servers into the ```$servers``` array is very dependant on how you want to feed it. You can read from a file, put the servers inline, use Nagios' database (suggested) if you have that setup, or your own custom method if you want to get advanced. Each server needs to have these properties: ip, version, master, and role with the server name as the key. By default, I set up the example with using a file.
* If you'd like to change the appearance of the bubbles, check out ```style.css```. You can set them however you like.

Outside of those things, you shouldn't need to change anything else! If you have any questions or issues, feel free to reach out to me.
