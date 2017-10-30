## Load Balancer & Reverse Proxy Setup

We have 2 servers! Yay! So... how do we configure things so that our users hit each
server randomly? Why, a load balancer of course! Setting up a load balancer has
nothing to do with Ansible, but let's take a quick tour anyways!

## Creating an Elastic Load Balancer

I've already loaded up my EC2 Control Panel. Click load balancers on the left to
create an "Elastic Load Balancer". To keep things simple, I'll create a "Classic"
load balancer, but you should use an "Application Loader" balancer. That type is
better, but a little more complex and beyond what I want to cover in this tutorial.

Give it a name - "MooTube-LoadBalancer" and make it respond only to HTTP traffic.
You can also configure your load balancer to allow https traffic... which is *amazing*,
because AWS can handle the SSL certificate automatically. Ultimately, the entire
SSL process is resolved by the load balancer, and *all* requests - including *secure*
requests - will be forwarded to our servers on port 80, as http. This means we get
https with basically no setup.

For the health check, keep `index.html.twig` - I'll talk about why in a minute.
I'm going to lower the interval and healthy threshold, but you can keep this: I'm
only doing this so that the load balancer will see our new servers faster.

Finally, select the 2 running instances: "Mootube Recording" is our original server -
I renamed it manually - and "Mootube instance" is the new server we just launched.

## Health Checks

Ok, create it! Look at the "Instances" tab: both servers are listed as "OutOfService".
That's normal: it's testing to make sure the servers work! How does it test? By
making a request to the IP address of each server, `/index.html`. 

Go copy the IP address to one of the servers and try this! Woh! It's the "Welcome
to Nginx" page from the default virtual host. *This* is why our health check
will pass.

A *better* setup might be to make MooTube our *default* virtual host, so that you
see the site *even* when you go to the IP address. That would be really nice because,
right now, even though the health check will pass, it doesn't actually mean that
MooTube is working on this server. It would be nicer to health check the *actual*
app.

Go back to the "Instances" tab. Yes! Both instances are now "InService".

## Testing the Load Balancer

So how can we test this? Every load balancer has a public DNS name. Copy that... then
try it in a browser! Oh... it's that same "Welcome to Nginx" page! Our load balancer
*is* sending our traffic to one of the servers... but since the host name is *not*
`mootube.example.com`, we see the *default* virtual host.

In a real situation, we would configure our DNS to point to this load balancer.
The Route 53 service in AWS let's you do this really easily. The tricky thing is
that, as you can see, it does *not* list an IP address for the load balancer! What!?
That's because the IP address might change at any time. In other words, you can rely
on the DNS name, but not the IP address.

Since this is a fake site... we can't setup the DNS properly. So, to test this,
we're going to cheat! Go to your terminal and ping the DNS name:

```terminal-silent
ping MooTube-ELB-Practice-21925007.us-east-1.elb.amazonaws.com
```

The ping will fail, but yes! There is the IP address to the load balancer. Like
I said, do *not* rely on this in real life. But for temporary testing, it's fine!
Edit your `/etc/hosts` file, and point this IP address to `mootube.example.com`.

Ok, let's try it! Open a new Incognito window and go to `http://mootube.example.com`.
Yes! It works! With no videos, this must be the new server! Refresh a few more times.
I *love* it: you can see the load balancer is randomly sending us to one of the two
servers.

## Reverse Proxy & X-Forwarded-* Headers

Now that we're behind a load balancer... we have a new, minor, but important problem.
Suppose that, in our app, we want to get the user's IP address. So, `$request->getClientIp()`.
Guess what? That will now be the *wrong* IP address! Instead of being the IP of
the user, it will *always* be the IP of the load balancer!

In fact, a *bunch* of things will be wrong. For example, `$request->isSecure()` will
return false, even if the user is accessing our site over `https`. The port and
even the host might be wrong!

This is a *classic* problem when you're behind a proxy, like a load balancer. When
the load balancer sends the request back to our server, it *changes* a few things:
the `REMOTE_ADDR` header is changed to be the *load balancer's* IP address. And
if the original request was an `https` connection on port 443, the new request will
*appear* insecure on port 80. That's because the load balancer handled the SSL stuff.

To help us, the load balancer sets the *original* information on a few headers:
`X-Forwarded-For` holds the original IP address and `X-Forwarded-Proto` will be
set to `http` or `https`.

***TIP
There are some standards, but the exact headers used can vary from proxy to proxy.
***

This means that our app needs to be smart enough to read *these* headers, instead
of the normal ones. Symfony doesn't do this automatically, because it could be a
security risk. You need to configure it explicitly.

## Setting Trusted Proxies

Google for "Symfony reverse proxy". Ok! In our front controller - so `app.php`
in Symfony 3, we need to call `setTrustedProxies()` and pass it all possible IP
addresses of our load balancer. Then, when a request comes into the app from
a trusted IP address, Symfony knows it's safe to use the `X-Forwarded` headers
and will use them automatically.

But... AWS is special... because we do *not* know the IP address of the load balancer!
It's always changing! In that case, copy the second code block. Open `web/app.php`
and - right after we create the request - paste it.

Thanks to this code, we're going to trust *every* request that enters our app.
Wait, what!? Doesn't that defeat the security mechanism? Yes! I mean... maybe!
When you trust *all* proxies like this, you *must* configure your servers to *only*
accept port 80 traffic from your load balancer. In other words, you need to configure
your EC2 instances so that you *cannot* access them directly from the public web.
The details of doing that are out of the scope of this tutorial. But once you've
done this, then it *is* safe to trust *all* requests, because your load balancer
is the *only* thing who can access your server.

Next! Let's get crazy, setup continuous integration, and auto-deploy our code after
the tests pass! Nice!
