# Ansible Roles

So our playbook is getting a little disorganized. There's just a lot of tasks going on here and I want to fix that. I also want to show you a way that you can leverage third party tasks to make your job a lot easier inside of your playbook.

So there are a few different categories of things happening inside of our playbook right now. One of them, I can kinda call "bootstrapping symphony," things that are specific to our symphony project. For example, installing the composer dependencies we do that on our symphony project ... and down below fixing the [Var 00:41] directory permissions is specific to symphony. All these consult commands including clear [to cashier 00:46] are specific to getting our symphony application up and running.

So I want to isolate those tasks into their own file. There's a couple ways to do that. The easiest way is to actually just include it. So I'm gonna create a new directory inside of Ansible called "includes", inside there, a new file called "symphony, bootstrap.yml" I'll put my dash, dash, dash, and then I'm just going to move certain tasks over here.

So I install composer dependencies, we'll take that out of our playbook, move it over here ... fix in the director permissions and then all the way down to the end, where we're clearing the cash. We'll get rid of all that stuff, and we'll paste it into our symphony boot-strap.

Now that this is in it's own file, we're actually going to un-indent it two times. So that our tasks are right are the root. And then inside of our playbook, to bring those tasks in, it's really cool ... Add a comment that says "symphony app" and then instead of actually running a module, we'll use "include", and we'll say, "./include/symphony-bootstrap.yml". It's very obvious how that works, it just goes- loads the tasks into here.

So the tasks will run this slightly different order than they did a second ago, but it won't make any difference. So make sure we didn't mess anything up, let's rerun our playbook, I'll use the 'deploy tab' on the end ...

Let's make it go a little faster ...

Beautiful. So including an external file's the easiest way to reuse tasks, but there's a better, more robust way. And it's called 'roles'. And roles are very important to Ansible, so if you think of a package of functionality, like bootstrap and symphony, or for example getting Nginx set up, it involves a number of things.

So to get Nginx set up, yes to obviously have some tasks that we need to run, but we also at the top set a variable called [servinate 3:43] this is a variable that we ultimately use inside of Nginx ...when we're bootstrapping our template ...

And there's also handlers- you go all the way down to the bottom, you'll remember that we've a handler called 'restart Nginx', which a few of our things refer to.

So, a collection of functionality, is really more than just tests; it's tests, variables, and sometimes other things. A role is a way to organize all of that stuff into a specific directory structure, and then Ansible will automatically include the handlers, the tasks, and the variables for you.

So lets do that for our Nginx installation and configuration. Let's put that in it's own role. And it's gonna look like this. Inside your Ansible directory create a new directory called 'roles' ... And inside of there another one called Nginx ... Now the way that roles work, is that you need to put certain files, in certain directories inside of role. As long as you do that, Ansible will automatically see that.

For example, we know that there's a few tasks that we wanna move into our role. So we're gonna create a new directory inside of here called 'tasks', and inside there, a new file called main.yml we'll put our dash, dash, dash, on top ...

And then down below, just like we did with our symphony bootstrap, we're just gonna move the tasks that are relevant into this. So inside my file, I'm gonna- search for 'install Nginx web server' that's obviously the one that we're gonna need. Move that over here ... And if you look down here, the other ones are ... adding the symphony config. Template to the Nginx available sites, enabling that, and then we'll also rope in, adding the "Nginxsidetwo/etc/host" So let's grab those three tasks, and put those in here as well. And just like with our symphony bootstrap, make sure that these are not indented anymore, they're now at the root.

Perfect, okay what else? Well you'll notice that, this refers to templates/symphony.conf which is at the root of our Ansible directory. Well this doesn't always need live in the role to work. So I'm actually gonna drag my templates directory up into my role. Perfect, let's keep going ...

As I mention earlier, and as you can see here, the role depends on variable [servinate 7:12]. So in addition to tasks, we really want this [servinate 7:19] variable to live inside the role as well. Well same thing, if you want to add a var, then you just need to follow the directory structure. And this creates, inside the Nginx directory, you're gonna create a 'vars' directory, and once again, inside there make main.yml file.

Click three tabs ... And the front playbook will take that server name [inaudible 00:07:51] move that out of here, and put it inside of here. It's not under the vars keyword anymore. Ansible knows this is a var, because it's inside of the 'vars' directory.

And finally, if you look back at our tasks, the last thing is we're using handlers. You can see that's saying 'notify, restart Nginx'. So inside of our playbook all the way at the bottom, we want to move this 'restart Nginx' handler, also into our role. So, copy and remove that, and same thing, it just needs to go into a specific spot, not surprisingly the directory this time is called 'handlers'. The file once again is called 'main.yml'. I put my three dashes, paste that there, and then un-indent it.

So that's the file structure for a role, and as long as you follow the file structure, Ansible will take care of including ... Ansible will take care of including all of that stuff for you ... Now to actually activate this inside of your main playbook file, up at the top, before tasks, thought the order doesn't actually matter, we're gonna say 'roles'. And then say 'Nginx'. Now it will know to go into the Nginx, the roles/Nginx directory, and include and run all that stuff.

All right, so lets try this. Flip over to your playbook ... run Ansible, but this time run the entire playbook, so don't include the deploy tab. And I want you to deploy it into the dev environment, and I'll show you why in a second ... And you can already see it working perfectly. Installing Nginx, and then taking care of the other things inside of there ... Whoa! I get a huge error at the end, ignore that for a second ... Go back to your browser and go to app_dev.php. And you'll see the same error. This is actually a product of us being a little bit too smart, inside of our playbook. It's unrelated to roles, the role worked, the problem was with ... the problem is inside our symphony bootstrap, where we only installed composer dependencies when the code changed. Well in this case, because we're able to deploy in the dev or the prod environment that actually treats composer dependencies a little bit differently. So I last deploy the prod environment and then I deploy the dev environment, I do want the composer to install its dependencies.

So this is where you need to find a right balance to see what works for you. So I'm gonna comment out that one co-changed. Now if we were always deploying to prod environment or the dev environment that would be totally fine to have. I'm gonna go, flip back and run my playbook one more time. In the dev environment. ...

This time composer dependencies is marked as 'changed' because it did download the dev dependencies. And everything works fine.

So flip over to your browser, go ahead and move to that l/app_dev.php ... and it works perfectly. So our role just worked. Now there is one small problem if you scroll up a little bit, in your outputs you'll notice that the role runs before we actually run the aft cash update. Which means it's going to install Nginx before it realizes that there might be new version of Nginx. So we didn't actually want it to run in that order.

You'll also notice that the only thing that ran before Nginx, other than the setup task, is converting the entered symphony environment to lowercase. So if you look at our playbook, all the way at the top you'll see pre tasks, roles, and tasks. And that's actually the order in which they run. Pre tasks are run first, then roles, then your tasks. And it doesn't matter which order you put them in the file. I happen to have them in the order that they run for clarity, it's always gonna run in that order.

So if we want to update our [inaudible 00:15:58] cash and upgrade our packages before running our roles, we need to move those up into the pre tasks. Awesome.

