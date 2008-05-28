require "find"
excludes = [".svn"]
exclude_files = []
triggers = []
output = ""
msgstr = ""
basedir = ARGV[0] || "."
Find.find(basedir) do |path|
  if FileTest.directory?(path)
    if excludes.include?(File.basename(path))
      Find.prune
    else
      next
    end
  else
    filename = File.basename(path)
    if filename =~ /\.php/ and not exclude_files.include?(filename)
      cleaned = path.sub("./", "")
      contents = File.read(path)
      if contents =~ /(\$trigger|Twigger::current\(\))->call\("[^"]+"(.*?)\)/
        counter = 1
        File.open(path, "r") do |infile|
          while (line = infile.gets)
            line.gsub(/(\$trigger|Twigger::current\(\))->call\("([^"]+)"(, (.+))?\)/) do
              args = $4 || ""
              output << $2 + ":\n\t" + args + "\n"
              triggers << $2
            end
          end
        end
      end
    end
  end
end
puts output