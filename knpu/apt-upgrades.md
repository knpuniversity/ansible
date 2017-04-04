# APT Upgrades

The APT module is going to be the key to getting so much stuff set up, but of course there's more with APT that you can do than just installing stuff. Usually, when you first go on to a server, you're going to need to update the packages. If you look at the APT module and you look at the options, there's actually one down here for update_cache, which is equivalent to the APT-Git update, which updates all the information from the mirrors. Probably when we first start the server, we're going to want to run that. Then afterwards, because the image might be out-of-date, we're probably going to want to upgrade existing packages, which we can do down here with the upgrade option.

So let's go back to our playbook. This is where the playbook becomes really awesome because we're just gonna start to chain more and more useful things together.

First, we're gonna update the APT package manager repositories cache. Most of this is gonna look familiar. We're gonna use become true, APT, and this time we'll use the other option, update cache. We'll set it to yes. Remember, yes and true, you can use either one you want.

Next, I'll copy that to upgrade the existing packages, to upgrade installed packages. Become true, APT, in this case we'll use the upgrade option, and I'm gonna set it to dist to start. You notice there's several values I have here. You can pick which one's best for you. We're actually gonna change that in a second, but dist is a valid option and it's equivalent to APT-Git dist upgrade. Upgrade, dist.

So that, we'll shoot back over here, run ads for dash playbook. The first time you run this it might actually take little bit of time because the machine might actually be out of date. There's a lot of repositories, cache and maybe a lot of packages that you want to upgrade. Not surprisingly, you can see that the upgrade install packages says, "Change." It did upgrade one or more packages behind the scenes.

Now one of the other options for upgrade is safe, which I kind of like because it does an Aptitude safe upgrade. Whenever you're upgrading, especially if these are important machines, obviously the bigger upgrade you do the more chance there is for problems. If you notice that the safe option actually does an Aptitude safe-upgrade, instead of APT-Git. Now that's not important except that sometimes on Ubuntu machines Aptitude might not actually be installed when you start. You might need to install it via APT-Git.

This is an important thing with modules. You notice that there's a requirement section that says that our machine actually needs to have a few things installed, particularly Python APT, Python 3 APT which we already have, and Aptitude for some of the modules. We think of modules as the standalone things that take care of everything for us, and that's true, but sometimes modules have requirements. You have to make sure that you install certain things before.

Now in our case on our machine, let's open a new tab. Use Vagrant SSH, and we'll try Aptitude. And you see Aptitude opens up, so we do have it installed. Let's change our setting to safe, flip back over, go back to our tab that's running as well and run it again. This time we shouldn't see any changes and we shouldn't see any errors because we do have Aptitude installed. Beautiful. Repository and packages upgraded, now we can go crazy and install everything we need.  I'm just gonna run down a list.

First we're gonna install the Git VCS because we're gonna use during our deployment process. Same thing become true, the modules APT, and this time we will go back to saying, "Name, Git." I'll move this below cowsay, not that the order is important in this case.

Before I try that, let's also go and install PHP.  So very simply I'll copy that block, paste it, and we'll install PHP CLI. This time it's gonna be called PHP5-CLI, and I realize we are installing PHP5, which is an old version. Stick with me, we're gonna upgrade that in a second.

So let's try this. Flip over. Clear. We'll run it. That works wonderfully. Let's go over to our virtual machine where we're already SHN, and we can do GIT -v. Do Git -- version 1.9.1. Perfect.

Now in addition to installing things, we can also actually control more or less what version we want. Down here it's in an option called "state". You can use the latest, absent, present, or build-dep. So we're asking here is "Do we just want to make sure that the package is installed? Or do want to make sure that it is actually always upgraded to the latest version? Or is it important for us for the build dependencies to also be there?" By default, it does present. I'm gonna change ours to state: latest to make sure we always have the latest version of Git.

If you'll flip back and run it right now, it won't make any difference because it just installed Git, so it already has the latest version, but now in the future when a new version of Git comes out, it's gonna grab those.

So next, let's get PHP installed.

