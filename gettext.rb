require "find"
require "yaml"
excludes = [".svn", "modules", "lib", "feathers", "themes"]
exclude_files = []
strings = []
lines = []
keys = ["name", "description", "plural", "notifications", "confirm"]
output = ""
msgstr = "XXX"
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
      if contents =~ /sprintf\(__\("(.*?)"(, ".*?")?\), (.*?)\)/
        counter = 1
        File.open(path, "r") do |infile|
          while (line = infile.gets)
            line.gsub!("\\\"", "{QUOTE}")
            line.gsub(/sprintf\(__\("(.*?)"(, ".*?")?\), (.*?)\)/) do
              unless strings.include?($1)
                output << '#: '+cleaned+':'+counter.to_s+"\n"
                output << '#, php-format'+"\n"
                output << 'msgid "'+$1+'"'+"\n"
                output << 'msgstr "'+msgstr+'"'+"\n\n"
              else
                unless lines.include?(cleaned+":"+counter.to_s)
                  output = output.gsub("#, php-format\nmsgid \""+$1+"\"\nmsgstr \""+msgstr+"\"\n\n", 
                                       "#: "+cleaned+":"+counter.to_s+"\n#, php-format\nmsgid \""+$1+"\"\nmsgstr \""+msgstr+"\"\n\n")
                end
              end
              strings << $1
              lines << cleaned+":"+counter.to_s
            end
            counter = counter + 1
          end
        end
      end
      if contents =~ /sprintf\(_p\("(.*?)", "(.*?)", (.*?)(, ".*?")?\), (.*?)\)/
        counter = 1
        File.open(path, "r") do |infile|
          while (line = infile.gets)
            line.gsub!("\\\"", "{QUOTE}")
            line.gsub(/sprintf\(_p\("(.*?)", "(.*?)", (.*?)(, ".*?")?\), (.*?)\)/) do
              unless strings.include?($1)
                output << '#: '+cleaned+':'+counter.to_s+"\n"
                output << '#, php-format'+"\n"
                output << 'msgid "'+$1+'"'+"\n"
                output << 'msgid_plural "'+$2+'"'+"\n"
                output << 'msgstr[0] "'+msgstr+'"'+"\n"
                output << 'msgstr[1] "'+msgstr+'s"'+"\n\n"
              else
                unless lines.include?(cleaned+":"+counter.to_s)
                  output = output.gsub("#, php-format\nmsgid \""+$1+"\"\nmsgid_plural \""+$2+"\"\nmsgstr[0] \""+msgstr+"\"\nmsgstr[1] \""+msgstr+"x\"\n\n", 
                                       "#: "+cleaned+":"+counter.to_s+"\n#, php-format\nmsgid \""+$1+"\"\nmsgid_plural \""+$2+"\"\nmsgstr[0] \""+msgstr+"\"\nmsgstr[1] \""+msgstr+"s\"\n\n")
                end
              end
              strings << $1
              lines << cleaned+":"+counter.to_s
            end
            counter = counter + 1
          end
        end
      end
      if contents =~ /__\("(.*?)"(, ".*?")?\)/
        counter = 1
        File.open(path, "r") do |infile|
          while (line = infile.gets)
            line.gsub!("\\\"", "{QUOTE}")
            line.gsub(/__\("(.*?)"(, ".*?")?\)/) do
              unless strings.include?($1)
                output << '#: '+cleaned+':'+counter.to_s+"\n"
                output << 'msgid "'+$1+'"'+"\n"
                output << 'msgstr "'+msgstr+'"'+"\n\n"
              else
                unless lines.include?(cleaned+":"+counter.to_s)
                  output = output.gsub("msgid \""+$1+"\"\nmsgstr \"\"\n\n", 
                                       "#: "+cleaned+":"+counter.to_s+"\nmsgid \""+$1+"\"\nmsgstr \""+msgstr+"\"\n\n")
                end
              end
              strings << $1
              lines << cleaned+":"+counter.to_s
            end
            counter = counter + 1
          end
        end
      end
    end
    if filename == "info.yaml" and not exclude_files.include?(filename)
      cleaned = path.sub("./", "")
      info = YAML.load_file(path)
      counter = 0
      info.each do |key, val|
        counter = counter + 1
        next unless keys.include?(key)
        
        if val.class == String
          val.gsub!("\"", "{QUOTE}")
          unless strings.include?(val)
            output << '#: '+cleaned+':'+counter.to_s+"\n"
            output << 'msgid "'+val+'"'+"\n"
            output << 'msgstr "'+msgstr+'"'+"\n\n"
          else
            unless lines.include?(cleaned+":"+counter.to_s)
              output = output.gsub("msgid \""+val+"\"\nmsgstr \"\"\n\n", 
                                   "#: "+cleaned+":"+counter.to_s+"\nmsgid \""+val+"\"\nmsgstr \""+msgstr+"\"\n\n")
            end
          end
        end
        if val.class == Array
          val.each do |val|
            val.gsub!("\"", "{QUOTE}")
            unless strings.include?(val)
              output << '#: '+cleaned+':'+counter.to_s+"\n"
              output << 'msgid "'+val+'"'+"\n"
              output << 'msgstr "'+msgstr+'"'+"\n\n"
            else
              unless lines.include?(cleaned+":"+counter.to_s)
                output = output.gsub("msgid \""+val+"\"\nmsgstr \"\"\n\n", 
                                     "#: "+cleaned+":"+counter.to_s+"\nmsgid \""+val+"\"\nmsgstr \""+msgstr+"\"\n\n")
              end
            end
          end
        end
        strings << val
        lines << cleaned+":"+counter.to_s
      end
    end
  end
end
output.gsub!("{QUOTE}", "\\\"")
puts '# Chyrp Translation File.'
puts '# Copyright (C) 2007 Alex Suraci'
puts '# This file is distributed under the same license as the Chyrp package.'
puts '# Alex Suraci <suracil.icio.us@gmail.com>, 2007.'
puts '#'
puts '#, fuzzy'
puts 'msgid ""'
puts 'msgstr ""'
puts '"Project-Id-Version: Chyrp v1.1.3\n"'
puts '"Report-Msgid-Bugs-To: suracil.icio.us@gmail.com\n"'
puts '"POT-Creation-Date: 2007-08-03 00:29-0500\n"'
puts '"PO-Revision-Date: '+Time.now.strftime("%Y-%m-%d %H:%M")+'-0500\n"'
puts '"Last-Translator: Alex Suraci <suracil.icio.us@gmail.com>\n"'
puts '"Language-Team: English (en) <suracil.icio.us@gmail.com>\n"'
puts '"MIME-Version: 1.0\n"'
puts '"Content-Type: text/plain; charset=UTF-8\n"'
puts '"Content-Transfer-Encoding: 8bit\n"'
puts ''
puts output