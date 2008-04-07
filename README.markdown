Chyrp
=====
Chyrp is a blogging engine designed to be lightweight while retaining functionality. It is driven by PHP and MySQL, and has a great standard theme and robust module engine. You can personalize and modify it any way you want.

All of your content is powered by a unique Feathers system that allows Chyrp to be whatever you want it to be. You can post anything and everything, or just stick to the default Text feather and run a regular blog. Chyrp destroys the fine line between a blog and a tumblelog.

Requirements
============
Chyrp will thrive on virtually any server setup, but we guarantee Chyrp to run on no less than:

* Apache 1.3.3
* PHP 4.4.7
* MySQL 4.1

These requirements are more of guidelines, as these are the earliest versions of the services that we have tested Chyrp on. If you are successfully running Chyrp on an earlier version of these services, let us know.

Installation
============
Installing Chyrp 1.1.3 is easier than you expect. You can do it in four steps:

1. Create a MySQL database with a username and password.
2. Download, unzip, and upload.
3. Open your web browser and navigate to where you uploaded Chyrp.
4. Follow through the installer.

That's it! Chyrp will be up and running and ready to do your bidding.

Upgrading
=========
Keeping Chyrp up to date is important to make sure that your blog is as safe as possible.

1. Download the latest version of Chyrp from [http://chyrp.net/](http://chyrp.net/).
2. Overwrite your current Chyrp installation files (be sure to back up `/themes`, `/modules`, and `/feathers`) with the new ones.
3. Re-install by navigating to [install.php]().
4. Upgrade your database by navigating to [upgrade.php]().
5. Restore any backups you made.

Extensions
==========
Chyrp isn't complete without activating a few extensions. Extensions add functionality (ex. audio clips, video, photos) to Chyrp. You can find extensions for Chyrp made by the Chyrp community at [http://chyrp.net/extend](http://chyrp.net/extend).

Installing Extensions
=====================
To install extensions, you have to determine what type of extension it is. It can be a *module*, a *feather*, a *theme*, or a *localization*. There's a different setup process for each type.

**Feathers**
Feathers add new *post types* to Chyrp. Post types determine what kind of media you can display in your blog.

1. Download and unzip the feather
2. Upload the feather to the `feathers/` folder.
3. Open your web browser and navigate to your Chyrp administration panel.
4. Click on the *Extend* tab, and then the *Feathers* sub tab.
5. Click the *enable* link beside the feather you just uploaded.

You can now use the feather by navigating to the Write tab and choosing the feather you uploaded.

**Modules**
Installing modules is quick, easy, and painless with Chyrp. They add extra functionality to Chyrp.

1. Download and unzip the module.
1. Upload the module to the `modules/` folder.
1. Open your web browser and navigate to your Chyrp administration panel.
1. Click on the *Extend* tab and click the *enable* link beside the module you just uploaded.

The module is now installed and is ready for action. Keep in mind that some modules may conflict with each other if they do similar tasks.

**Themes**
Chyrp makes applying themes to your blog easy. With a single click you can change the look of your blog.

1. Download and unzip the theme.
2. Upload the theme to the `themes/` folder. Make sure that it is contained in it's own folder.
3. Open your web browser and navigate to your Chyrp administration panel.
4. Click on the *Extend* tab, and then the *Themes* sub tab.
5. Click on the screenshot of the theme you just uploaded to apply it to your blog.

Chyrp can even show you what the theme will look like before anyone else sees it. In the Themes sub tab, click on the Preview button below the theme screenshot to see the theme.

**Localization**
Chyrp is multilingual! If your first language isn't English, you can apply a new localization to Chyrp to make it speak your language.

1. Download and unzip the localization.
1. Upload the `.mo` file to the `includes/locale/` folder. You don't need anything else for the translation to work.
1. Open your web browser and navigate to your Chyrp administration panel.
1. Click on the *Settings* tab, and change the *Language* option to the language you just uploaded.

Chyrp is in need of people like you to help localize Chyrp to your language.