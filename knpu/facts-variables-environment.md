# Facts Variables Environment

One of the things we're missing right now for a Symfony deploy is actually clearing the Symfony cache after we're done with everything. Let's do that. Set a new task, call it "Clear Cache." And if you're a Symfony user, you know this is a very simple in consol command. So I'll say "Symfony consol path", use the variable we set earlier.

Cache:clear --env=prod

Easy enough. Now, before I even try that, this '--env=prod' is going to become interesting because really when we deploy our application sometimes we might want to deploy in the prod environment, and then we should be clearing the prod cache, but other times we might be deploying it in the dev environment, we might be deploying it to a dev machine and we want to see it in the dev environment.

So in theory, this production environment should be configurable. And actually, this is going to become important in two places. First, I wan this '--env=' part to be configurable. Second, if you Google for Symfony deploy, you scroll down to a section about installing and updating your vendors, you'll see that it recommends when you deploy saying, "Composer installed '--no-dev'". Now remember, that's actually what the composer module did ... by default. But then we, via a no dev option, actually told it to stop doing that. And the reason we did is because it caused a whole bunch of errors to happen. Because Symfony has some post install composer commands, that are incompatible with not installing the dev requirements.

Now in theory this '--no-dev' flag is not that big of a deal, whether or not you have it or don't have it. But if you want to have a '--no-dev' flag you can, as long as you set an environment variable called 'Symfony_env=prod'. By doing this, when the post-install commands run, those post-install commands will be smart enough to not try and do certain things that make use of the require -dev dependencies.

So not only do I want to know what the environment is so that I can use it to configure our cache clear command, I also want to start setting this environment variable. So how can we do that? First, to configure wether or not we want the prod environment or the dev environment, one thing you can do is you can actually add a prompt.

Add a vars prompt key and let name: Symfony_env

That's going to be the name of a new variable we're setting.

Prompt: "Enter the environment for your Symfony app"

Will probably be one of prod or dev or test. And we'll default that to prod, and then we'll also set private to no, which just controls whether or not you type this at the command line, whether or not it has the little asterisk, whether it hides your input.

Okay. So just by having this, it's now going to ask us this question, it's going set this new 'Symfony_env' variable. Which we can use in a second.

But before we do, the next question is how can we set this 'Symfony_env' environment variable?

Well fortunately setting environment variables is pretty common, so Ansible has an environment section. So we're going to say, "Environment:", and here we'll say Symfony and env in all caps. And we'll set that to '{{Symfony_env|lower}}'. So we're using the 'Symfony_env' variable that we just set a second ago, and we're piping it through lower just to make sure that if we did prod with an uppercase P that it set to lowercase.

All right, to see what this all looks like, at the top let's debug a couple of variables. First I want you to debug a variable called 'Ansible_env'. This is a built-in variable that contains lots of information about the environment, the host environment that you're working on. And it should also contain our environment variable.

And second, let's just debug our 'Symfony_env' variable, which should be set here. And then before I run that, down on our cache clear at the bottom, I forgot to add my tag for deploy. It's not going to be important quite yet, but we want to make sure that that runs when we deploy.

All right, let's flip over to our host machine and run our playbook, and I'll take off my tag so that it runs everything. And then immediately, it asks us for the environment. Awesome, I'm going to leave it blank to use the prod environment. And then I'm actually going to control C. Soon as it's done here, you can see it printed out our two things.

First thing it printed out was the 'Ansible_env', which by the way, has a lot of really important stuff on it because it's got the environment variables on our host machine. Which sometimes has some pretty interesting stuff, like the 'Home' key, for the home directory, the current directory, and other information. But it also now has our 'Symfony_env' set to prod, which is awesome. And not surprisingly, our 'Symfony_env' Ansible variable is set below, as well.

Now I few run this again with uppercase PROD ... not surprisingly, the environment is set to lowercase prod, but internally our 'Symfony_env' variable is set to uppercase PROD. So that's going to be a problem in a second, because in a second I'm going to want to use this in a few other places, like down here on my cache clear command.

Now of course, when we do that, we could always keep using the '|lower' in every place that we use the 'Symfony_env', or we could make this change globally. We can guarantee globally that the 'Symfony_env' variable is always going to be lowercase. And a really nice way to do that is with a pre-task.

So add a new key called pre-tasks and add a pre-task whose name is 'Convert entered Symfony environment to lowercase'. Now pre-tasks are exactly like tasks, they run modules. The only difference is that pre-tasks run before tasks. So you might be wondering why I'm bothering putting this as a pre-task at all, because I could just put it at the top of my tasks.

The answer is that it's not important yet, but will be important later, when we introduce something called 'roles'. Roles are a way for us to package a bunch of functionality and run it all at once, and it turns out that roles run before tasks, but pre-tasks run before roles. But we'll talk more about that later.

Now what we want to do here is we basically want to take the 'Symfony_env' variable and set it to a lowercase version of itself. To do that, we're going to use a module called 'set_fact'. Which is a way to set variables in the system. You remember before, we already learned that you can set variables by using the register key under a task. So the final output information of this task is set to a DB migrations result variable. But other times, you may want to have more control. You might want to set a variable to some exact value. And to do that, you are going to use the 'set_fact' module.

The way it works like this is under it you can just set as many variables as you want. So we're going to set a variable called 'Symfony_env', and we're going to set this to {{Symfony_env|lower}}. And like with tasks, these need tags on them. So we probably wan this to run all the time, so we can either tag this with deploy, but I'm actually going to tag it with 'always', just to make sure that this always runs.

So we try this now, entering in PROD in all caps again. Now we see our prod is lowercase, which is perfect. So we can remove our two debug tasks. Now you might expect me to remove the '|lower' from the environment, because after all 'Symfony_env' is now set to a lowercase, but we actually can't. The environment runs before all of our tasks. So actually, this environment key runs before our pre-task is able to set that to a lowercase value. And it has nothing to do with the ordering in the [ML file 00:10:27]. I put the environment above pre-tasks because it makes sense, that's the order in which it runs, but in all cases environment is going to run before pre-task, so I'm going to keep that '|lower' in that spot.

So now that we know that we have a new variable called 'Symfony_env', we can use it in a couple of places. The first one is actually down in our composer section. Before we said, "No dev no." But now we know that if the environment is prod, it actually should be okay for us to say 'yes' to no dev. So to do that, we're going to use a bit of a complex expression here. This is actually [jinja 00:11:04]. We can say, "{{'yes' if (prod == Symfony_env) else 'no'}}" And don't forget to surround both sides with your curly curly, to actually open up into [jinja 00:11:26]. So if 'Symfony_env=prod', no dev should be 'yes', else it should be 'no'.

All right, where else can we use this? If we look down a little bit further, we loaded data fixtures. Again, if we're loading to our prod environment, we might not want to actually load the data fixtures. [inaudible 00:11:51] depends on how you're setting up your app to deploy. But so far, we don't have any way to conditionally run a task, tasks are always run. We learnt how to control their change status, but we don't have a way yet to say, "Only run this task under these scenarios."

Well guess what. To do that, you can use the 'when' key. In this case, it's very simple. We're going to say, "when: '{{Symfony_env does not = "prod"}}'". And here, we don't even nee dot open [jinja 00:13:27] because this is going to assume that we're already writing inside of [jinja 00:13:33].

And finally, down here in our cache clear, instead of prod we can say, "{{Symfony_env}}".

All right, let's give this whole thing a try. So I'll flip back to my host machine ... and I'll rerun our playbook. And I used the '-t deploy', and used the prod environment. Because even with '-t deploy', it does prompt us for the vars, it should run our pre-task, which is tagged with always, and then all the changes that we just made down here are inside the deploy tag. So we should see it install composers dependencies with '--no-dev', and we should see it skip loading the data fixtures and clearing the cache in the prod environment.

So you can see 'changed uninstalling composers dependencies', that's not a surprise because it did not have the dev dependencies. Skipped the fixtures, and cleared the cache. And in fact, if we look at our project and inside 'ls vendor/sensio', you will not see the generator bundle, because the generator bundle is something that is inside of required dev. So it worked perfectly.

And before we go, one last thing that I'm going to add down here, is I'm going to say, "Changed when to false" on cache clear. Again, because really every time we run that, we don't want it to look like it changed but it really doesn't matter.

