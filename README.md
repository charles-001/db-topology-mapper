# Database Topology Mapper

![DB Topology Mapper](https://i.imgur.com/3UvPJto.png)

```Database Topology Mapper``` is a tool that pairs master & slave servers into a beautiful map using D3.js that is easily digestible for DBAs and anyone else in your organization.

# Key things to change to fit your infrastructure
* ```$environments``` array is to separate servers into their own branch. The masters for that environment must have its master set to the environment name for it to show under that branch. Once that's done, you'll need to change index.php to set the environments to whatever color you want. You can find them under  ```nodeEnter.append("rect")```. Gray is the default color. For more guidance on setting it up, just look at how I did it. 
* Getting servers into the ```$servers``` array is very dependant on how you want to feed it. You can read from a file, put the servers inline, use Nagios' database (suggested) if you have that setup, or your own custom method if you want to get advanced. Each server needs to have these properties: ip, version, master, and role with the server name as the key.
* If you'd like to change the appearance of the bubbles, check out ```style.css```. You can set them however you like. 

Outside of those things, you shouldn't need to change anything else! If you have any questions or issues, feel free to reach out to me. 
