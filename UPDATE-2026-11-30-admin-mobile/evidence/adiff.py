import os, json
from PIL import Image, ImageChops
pages=['products','product-edit','quotes','media','analytics','settings','users','photo-review','packaging','homepage','pages']
rows=[]
for loc in ('en','ar'):
    for w in (768,1024,1440,1920):
        for pg in pages:
            a=f'reg/before-{loc}/{pg}-{w}.png'; b=f'reg/after-{loc}/{pg}-{w}.png'
            if not (os.path.exists(a) and os.path.exists(b)): rows.append(dict(loc=loc,w=w,page=pg,status='missing')); continue
            A=Image.open(a).convert('RGB'); B=Image.open(b).convert('RGB')
            if A.size!=B.size: rows.append(dict(loc=loc,w=w,page=pg,status='SIZE',a=A.size,b=B.size)); continue
            d=ImageChops.difference(A,B); bbox=d.getbbox()
            if bbox is None: rows.append(dict(loc=loc,w=w,page=pg,status='identical',size=A.size)); continue
            n=sum(1 for px in d.getdata() if px!=(0,0,0))
            rows.append(dict(loc=loc,w=w,page=pg,status='DIFF',pixels=n,total=A.size[0]*A.size[1],bbox=bbox))
json.dump(rows,open('adiff.json','w'),indent=1)
from collections import Counter
print(Counter(r['status'] for r in rows))
for r in rows:
    if r['status']!='identical': print(r)
