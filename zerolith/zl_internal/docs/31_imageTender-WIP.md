Draft 10/10/2023 - DS

- The pitch

ImageTender offers automatic advanced image recompression for any web application that's roughly equivalent ( in terms of compresison ratio ) with the software that CDNs and large sites like YouTube and Facebook use to manage the growth of uploaded images. It is designed to have very low system requirements and can easily run on a 2 core / 4gb mem production system.

In order to achieve this, we use mozjpeg ( Trellis quantization ), pngquant ( lossy ), and gifsicle ( lossy ). These advanced recompressors use much more CPU intensive algorithms than usual, but yield 20-50% smaller images without any degredation in image quality. Image Tender can also resize images to a maximum size and strip metadata to reduce the file size further.

Under the hood, ImageTender is a frontend to advanced compressors that adds many safety checks to prevent image loss, as well as compression tracking so that it doesn't accidentally compress images more than once. It can be installed on an ordinary linux server and ran on a cronjob to perpetually optimizes images in a folder; side-stepping processing any non-image content in that folder.

It is designed to be a maintenance free way to add image optimizing to small to mid sized websites that don't have the technical resources to build something like this themselves, or pay for an external image recompression service. Image Tender can be safely ran on a small production server with at least 1-2GB of ram.

- Features:

- No exotic install requirements: uses PHP and MySQL in addition to 4 command line utilities that can be easily sudo apt install'd on ubuntu
- Utilizes advanced image recompressors like gifsicle, pngquant, and mozjpeg, which can improve compression of images VS stock compressors by 20-50%
- Can resize images to a maximum X/Y, allowing for a 'slack factor' to prevent a 2200x2200 image from being resized to 2000x2000 ( such small rescaling ratios can heavily degrade an image ). This feature can reduce file sizes further.
- Strips unnecessary metadata with exiftool, only retaining the rotation parameter; nice for increasing your user's privacy; also reduces file size a tiny bit.
- Verifies an image is valid before attempting to compress it. If it's corrupt, it skips recompression so we don't accidentally damage the image more.
- Can detect and copy corrupt images to a seperate folder.
- Avoids unnecessary image quality loss by keeping the base image if it was not a certain % smaller after being recompressed.
- It Inspects files to find out what their MIME type is before processing them. If the MIME type is not a supported image type, it is not processed.
- Can work on any file folder recursively; point it at the image store folder of any application
- Runs 'behind' the application that allows uploading images so that image compression time doesn't negatively affect user experience or require integration into the application.

- Why Image Tender was created

We were running an ad-free ebike forum on a shoestring budget. Increasing storage and bandwidth costs were nipping at our ability to do that sustainably. In our forum software, to control costs, we would also restrict photo uploads to a certain dimensions and megabyte count. The software could not resize the image automatically, so it would automatically reject uploads above those parameters. This was very inconvenient for our users. We wanted image uploading to work the same way it did on social media sites.

We were also about to change to a different forum software in a few years, so we wanted a tool that would work on the file system level instead of application level to perpetually keep the images compressed as tightly as possible, regardless of what software we were using.

- Our challenge:

Dozens of off-the-shelf image processing tools we tested were incapable of handling our data set. All of them would choke, break images, or just fail to work. And also none of them were designed for a recurring maintenance process that avoided recompression.

Our situation:
- 250,000 images totaling 100gb
- Dataset was 16 years old, possibility of differences in image formats
- Multiple eras of corruption and different types of corruption across the entire store
- Possibility of a malicious image in such a large dataset; processing with a windows-based tool inadvisable
- files had no extensions, and were mixed in with non-images ( exe files, zip files, ppt, etc ) also without extensions
- With such a large image set, there is almost no way to perform a full before/after to check the process, so the image processing code must be absolutely perfect itself.
- The first pass of processing must run on a 2 core / 4gb web server without maxing out the CPU/RAM - must have a throttling mechanism

- What Image Tender did for us:

ImageTender achieved a 68.35% file size reduction on our 100gb dataset without degrading image quality from a set of images that wasn't rescaled to a maximum size, whose jpgs were re-compressed from user uploads with GD to 85% quality and PNG/GIF files were left unprocessed. The total dataset took 27 hours to process due to the single core limitation of ImageTender v1.0. The website was not interrupted during the 2 day operation due to Image Tender's basic throttling.

This produced significant savings our costs for bandwidth and backup sizes right now, which continue to add up as our site grows.

For this reason, we think that imageTender may also work for other people

- Installation Procedure:

You will need several command line utilities installed. Here are instructions for installing those on ubuntu:
sudo apt install mozjpeg
sudo apt install pngquant
sudo apt install gifsicle
sudo apt install exiftool

setup a new mysql database and write the information down.
insert that database information into /zl_config.php
edit it_config.php and set the desired parameters
in a web browser, run /zerolith/zl_install.php to install imageTender's database table.

Do this on a copy of your image directory first to ensure everything is OK.
run /imageTender.php in a browser a few times to understand it's control loop and see it in action.
If everything looks correct, put the imageTender.php in a cronjob and enjoy!


- Functionality we want but don't have yet:

- Multiple core image processing; useful for websites much larger than ours which have a large amount of images being uploaded per minute.
- Image analysis and auto-transcoding of photographic-like images from .png to .jpg if it looks better; and line-art/text-like images from .jpg to png if it looks better. This will require application specific functionality, though.
- Post processing like automatic sharpening after image resize
- Support for webP, jpegXL, and other future image formats