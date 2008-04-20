from path import *
import re

for f in path(".").walkfiles("*.php"):
	source = f.text()
	sanitized = re.sub("[\t ]+\n", "\n", source)
	f.write_text(sanitized)