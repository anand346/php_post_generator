from PIL import Image
import glob

imagelist = []
im1 = None
flag = 1
for path in glob.glob("./downloads/*"):
    if flag == 1 :
        image1 = Image.open(path,mode='r')
        im1 = image1.convert('RGB')
        flag += 1
    else :
        image = Image.open(path,mode='r')
        imageRgb = image.convert('RGB')
        imagelist.append(imageRgb)
        print(path)

im1.save(r'./pdf/myImages.pdf',save_all=True, append_images=imagelist)