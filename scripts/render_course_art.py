#!/usr/bin/env python3
"""Original UAV Visuals concept maps and technical illustrations. Build-time only."""
import html
import json
from pathlib import Path
import textwrap
from PIL import Image, ImageDraw, ImageFont

ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'moodle/local/skyscholarcontent/content/illustrations'
SVG=ROOT/'authoring/illustrations'
OUT.mkdir(exist_ok=True); SVG.mkdir(exist_ok=True)
INK='#243d31'; PAPER='#f8f8ef'; GOLD='#d7c692'; MUTED='#526b5b'
F=lambda n:ImageFont.truetype('DejaVuSans.ttf',n)
B=lambda n:ImageFont.truetype('DejaVuSans-Bold.ttf',n)
def wrapped(text,width):return textwrap.wrap(text,width=width,break_long_words=False)
def base(title):
 im=Image.new('RGB',(1600,900),PAPER);d=ImageDraw.Draw(im)
 d.text((75,45),'UAV VISUALS  /  SKYSCHOLAR',font=B(24),fill=MUTED)
 for i,line in enumerate(wrapped(title,58)):d.text((75,105+i*47),line,font=B(35),fill=INK)
 d.line((75,240,1525,240),fill=GOLD,width=4)
 return im,d

def flow(slug,i,unit):
 title=unit['title'];labels=unit['diagram']; im,d=base(title)
 boxes=[(90,300,730,465),(870,300,1510,465),(870,585,1510,750),(90,585,730,750)]
 for j,(box,label) in enumerate(zip(boxes,labels)):
  d.rounded_rectangle(box,radius=22,fill='#ffffff',outline='#c4d2c5',width=3)
  x,y,x2,y2=box;d.ellipse((x+25,y+40,x+85,y+100),fill=INK)
  d.text((x+42,y+51),str(j+1),font=B(28),fill=PAPER)
  for k,line in enumerate(wrapped(label,27)):d.text((x+110,y+38+k*40),line,font=B(27),fill=INK)
 for pts,tri in [([(745,383),(850,383)],[(850,383),(830,369),(830,397)]), ([(1190,480),(1190,565)],[(1190,565),(1176,545),(1204,545)]), ([(850,668),(750,668)],[(750,668),(770,654),(770,682)])]:
  d.line(pts,fill=MUTED,width=5);d.polygon(tri,fill=MUTED)
 d.text((90,815),'Concept map. Read with the lesson; not an operational procedure or scaled diagram.',font=F(22),fill=MUTED)
 name=f'{slug}-{i:02}.png'; im.quantize(colors=64,dither=Image.Dither.NONE).save(OUT/name,optimize=True)
 parts=[f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900"><rect width="1600" height="900" fill="{PAPER}"/><title>{html.escape(title)}</title><g font-family="sans-serif" fill="{INK}">']
 for k,line in enumerate(wrapped(title,58)):parts.append(f'<text x="75" y="{135+k*47}" font-size="35">{html.escape(line)}</text>')
 for j,(box,label) in enumerate(zip(boxes,labels)):
  x,y,x2,y2=box;parts.append(f'<rect x="{x}" y="{y}" width="{x2-x}" height="{y2-y}" rx="22" fill="white" stroke="#c4d2c5"/>')
  for k,line in enumerate(wrapped(f'{j+1}. {label}',35)):parts.append(f'<text x="{x+30}" y="{y+65+k*40}" font-size="28">{html.escape(line)}</text>')
 parts.append('</g><path d="M745 383h100m-15-12 15 12-15 12 M1190 480v80m-12-15 12 15 12-15 M850 668H755m15-12-15 12 15 12" fill="none" stroke="#526b5b" stroke-width="5"/></svg>')
 (SVG/name.replace('.png','.svg')).write_text(''.join(parts))

def arrow(d,a,b,label,offset=(0,0),fill=INK):
 import math
 d.line((a,b),fill=fill,width=7);angle=math.atan2(b[1]-a[1],b[0]-a[0])
 p1=(b[0]-25*math.cos(angle-.5),b[1]-25*math.sin(angle-.5));p2=(b[0]-25*math.cos(angle+.5),b[1]-25*math.sin(angle+.5))
 d.polygon((b,p1,p2),fill=fill);d.text((b[0]+offset[0],b[1]+offset[1]),label,font=B(26),fill=fill)

def special():
 im,d=base('Thrust components in an idealised tilted hover')
 d.polygon([(551,435),(841,602),(829,624),(539,457)],fill=INK)
 arrow(d,(690,535),(690,755),'W: weight',(-35,22))
 arrow(d,(690,535),(817,315),'T: total thrust',(15,-10))
 arrow(d,(690,535),(690,315),'T cos(theta)',(-235,-15),MUTED)
 arrow(d,(690,535),(817,535),'Horizontal component',(10,10),MUTED)
 d.text((95,340),'Vertical balance:',font=B(30),fill=INK);d.text((95,390),'T cos(theta) = W',font=F(29),fill=INK)
 d.text((95,455),'If W = 100 N and theta = 30°',font=F(24),fill=MUTED);d.text((95,495),'T ≈ 115.5 N',font=B(30),fill=INK)
 d.text((95,820),'Simplified force geometry only. Not a complete aircraft model or a control instruction.',font=F(22),fill=MUTED)
 im.quantize(colors=64,dither=Image.Dither.NONE).save(OUT/'thrust-components.png',optimize=True)
 im,d=base('Image sampling is not a positional accuracy claim')
 d.rounded_rectangle((680,290,920,355),radius=12,fill=INK)
 d.polygon([(800,355),(350,680),(1250,680)],fill='#e1e8da',outline=MUTED)
 d.line((270,680,1320,680),fill=INK,width=6)
 d.text((100,345),'Camera',font=B(29),fill=INK);d.text((100,395),'Pixel pitch p; focal length f',font=F(26),fill=MUTED)
 d.text((870,455),'Local height H',font=B(27),fill=INK)
 d.text((100,735),'Simplified flat-ground / nadir model: GSD ≈ H × p / f',font=B(30),fill=INK)
 d.text((100,795),'80 m × 2.4 μm / 8 mm = 0.024 m = 2.4 cm/pixel',font=F(27),fill=MUTED)
 im.quantize(colors=64,dither=Image.Dither.NONE).save(OUT/'camera-sampling.png',optimize=True)
 im,d=base('Control fits the model; checkpoints test it independently')
 d.rounded_rectangle((100,290,950,760),radius=10,fill='#e5eddf',outline=INK,width=4)
 controls=[(220,380),(750,390),(350,640),(820,650)];checks=[(540,470),(620,650),(280,520)]
 for x,y in controls:d.ellipse((x-17,y-17,x+17,y+17),fill=INK)
 for x,y in checks:
  d.line((x-16,y-16,x+16,y+16),fill='#966b2b',width=6);d.line((x-16,y+16,x+16,y-16),fill='#966b2b',width=6)
 d.text((1020,360),'● Control',font=B(30),fill=INK);d.text((1020,420),'Used in fitting',font=F(25),fill=MUTED)
 d.text((1020,530),'× Checkpoint',font=B(30),fill='#966b2b');d.text((1020,590),'Withheld from fit',font=F(25),fill=MUTED)
 d.text((100,820),'Illustrative layout only. Point count/distribution must suit the actual specification.',font=F(22),fill=MUTED)
 im.quantize(colors=64,dither=Image.Dither.NONE).save(OUT/'control-checkpoints.png',optimize=True)
 im,d=base('Markup and margin have different denominators')
 rows=[('Cost',4000,INK),('Selling price',5000,'#66835f'),('Gross contribution',1000,'#b39758')]
 for i,(label,value,colour) in enumerate(rows):
  y=300+i*130;d.text((100,y),label,font=B(28),fill=INK);d.rectangle((520,y,520+value/6,y+65),fill=colour);d.text((520+value/6+20,y+10),f'R{value:,}',font=F(27),fill=INK)
 d.text((100,735),'Markup: 1,000 ÷ 4,000 = 25%     Margin: 1,000 ÷ 5,000 = 20%',font=B(28),fill=INK)
 d.text((100,805),'Fictional simplified costs/prices, excluding taxes and other costs. Not a market-rate quote.',font=F(22),fill=MUTED)
 im.quantize(colors=64,dither=Image.Dither.NONE).save(OUT/'markup-margin.png',optimize=True)

def additional():
 def save(im,name):im.quantize(colors=64,dither=Image.Dither.NONE).save(OUT/name,optimize=True)
 im,d=base('Aircraft axes: orientation is not ground track')
 d.ellipse((655,405,945,695),fill='#dfe8d8',outline=INK,width=5)
 d.line((800,320,800,750),fill=INK,width=18);d.line((590,550,1010,550),fill=INK,width=18)
 arrow(d,(800,550),(800,285),'Longitudinal axis',(-350,-30));arrow(d,(800,550),(1250,550),'Lateral axis',(10,-20))
 d.arc((630,380,970,720),20,300,fill='#967632',width=7)
 d.text((100,710),'Roll: longitudinal rotation',font=B(27),fill=INK)
 d.text((100,755),'Pitch: lateral rotation',font=B(27),fill=INK)
 d.text((100,800),'Yaw: rotation about the vertical axis (out of this top view)',font=B(27),fill=INK)
 save(im,'aircraft-axes.png')
 im,d=base('Exposure choices depend on the intended output')
 for i,(title,lines) in enumerate([('Shutter time',['Motion appearance','Blur / sharpness']),('Aperture (if adjustable)',['Light and optics','Camera-specific limits']),('ISO setting',['Image response','Noise tradeoffs'])]):
  x=90+i*505;d.rounded_rectangle((x,330,x+450,650),radius=20,fill='white',outline=MUTED,width=3)
  d.text((x+25,365),title,font=B(27),fill=INK)
  for j,line in enumerate(lines):d.text((x+25,450+j*65),line,font=F(25),fill=MUTED)
 d.text((90,765),'Inspect actual files for the deliverable; one setting is not suitable for every scene.',font=F(27),fill=INK)
 save(im,'exposure-choices.png')
 im,d=base('Storyboard: give the audience context before detail')
 labels=['1 Establish place','2 Connect areas','3 Show activity','4 Locate feature','5 Show detail','6 Close the story']
 for i,label in enumerate(labels):
  x=90+(i%3)*505;y=285+(i//3)*250
  d.rounded_rectangle((x,y,x+450,y+185),radius=10,fill='#e3ebde',outline=MUTED,width=3)
  scale=35+(i%3)*25;cx=x+225;cy=y+90
  d.rectangle((cx-scale,cy-scale//2,cx+scale,cy+scale//2),fill=INK)
  d.text((x+15,y+198),label,font=B(24),fill=INK)
 d.text((90,835),'Original schematic frames, not captured footage or an operational flight plan.',font=F(23),fill=MUTED)
 save(im,'storyboard-sequence.png')
 im,d=base('A thermal display is not a diagnosis')
 d.rounded_rectangle((110,370,460,650),radius=12,fill='#c6d7bc',outline=INK,width=4)
 d.text((140,680),'Observed surface',font=B(26),fill=INK)
 arrow(d,(480,430),(1020,430),'Emitted / reflected radiation',(-500,-65))
 d.rounded_rectangle((1040,360,1450,640),radius=12,fill=INK)
 d.text((1080,405),'Sensor +',font=B(32),fill=PAPER);d.text((1080,460),'measurement model',font=B(27),fill=PAPER)
 d.text((100,765),'Conditions, emissivity, reflection and calibration affect interpretation.',font=F(27),fill=INK)
 d.text((100,815),'Palette colours alone do not establish a fault or its cause.',font=B(27),fill=INK)
 save(im,'thermal-interpretation.png')
 im,d=base('Phonetic practice: reconstruct the letters you hear')
 words=['Alfa','Bravo','Charlie','Delta','Echo','Foxtrot','Golf','Hotel','India','Juliett','Kilo','Lima','Mike','November','Oscar','Papa','Quebec','Romeo','Sierra','Tango','Uniform','Victor','Whiskey','X-ray','Yankee','Zulu']
 for i,word in enumerate(words):
  x=70+(i%7)*210;y=280+(i//7)*135
  d.rounded_rectangle((x,y,x+195,y+115),radius=10,fill='white',outline='#c4d2c5',width=2)
  d.text((x+15,y+12),chr(65+i),font=B(32),fill=INK);d.text((x+15,y+62),word,font=F(24),fill=MUTED)
 d.text((75,850),'English ICAO-style spelling practice. Local procedures require current-source/instructor review.',font=F(21),fill=MUTED)
 save(im,'phonetic-alphabet.png')
 im,d=base('A simplified headwind calculation: keep frames distinct')
 arrow(d,(150,360),(1350,360),'Air-relative speed 6 m/s',(-700,-60))
 arrow(d,(550,535),(150,535),'Direct headwind 2 m/s',(50,-60), '#967632')
 arrow(d,(150,700),(950,700),'Groundspeed 4 m/s',(-650,25),MUTED)
 d.text((90,830),'One-dimensional teaching model only; not a safe-endurance or mission-planning method.',font=F(23),fill=MUTED)
 save(im,'wind-frames.png')
 im,d=base('Assigning a CRS and transforming coordinates are different')
 d.rounded_rectangle((100,350,650,620),radius=15,fill='white',outline=INK,width=4)
 d.rounded_rectangle((950,350,1500,620),radius=15,fill='white',outline=INK,width=4)
 d.text((140,395),'Source values',font=B(32),fill=INK);d.text((140,465),'(longitude, latitude)',font=F(28),fill=MUTED)
 d.text((990,395),'Target values',font=B(32),fill=INK);d.text((990,465),'(easting, northing)',font=F(28),fill=MUTED)
 arrow(d,(680,490),(920,490),'',fill=MUTED)
 d.text((540,290),'Valid coordinate transformation',font=B(27),fill=INK)
 d.text((100,720),'Assign CRS: define the interpretation of existing coordinate numbers.',font=F(28),fill=INK)
 d.text((100,780),'Transform: compute values using a valid source-to-target operation.',font=F(28),fill=INK)
 save(im,'crs-transform.png')
 im,d=base('Nominal battery energy is a capacity comparison')
 d.rounded_rectangle((180,330,700,660),radius=20,fill='#dce7d5',outline=INK,width=5)
 d.rectangle((700,430,740,560),fill=INK)
 d.text((220,380),'14.8 V × 6 Ah',font=B(40),fill=INK);d.text((220,465),'= 88.8 Wh',font=B(43),fill=INK)
 d.text((850,365),'Usable duration also depends on:',font=B(27),fill=INK)
 for i,label in enumerate(['Aircraft / payload load','Condition and environment','Approved reserve / procedure']):d.text((850,440+i*65),label,font=F(26),fill=MUTED)
 d.text((100,805),'Fictional label values. Capacity arithmetic does not establish safe flight endurance.',font=F(25),fill=MUTED)
 save(im,'battery-energy.png')

if __name__=='__main__':
 count=0
 for path in sorted((ROOT/'authoring/full-courses').glob('*.json')):
  course=json.loads(path.read_text())
  for i,unit in enumerate(course['units'],1):flow(course['slug'],i,unit);count+=1
 special();additional()
 print(f'Rendered {count} original concept maps + 12 technical diagrams')
