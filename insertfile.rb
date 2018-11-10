#!/usr/bin/ruby
# encoding: UTF-8
# -*- coding: UTF-8 -*-

require 'optparse'
require 'ostruct'
require 'pp'


# 解析参数

options = OpenStruct.new
options.insertion = nil
options.search = nil
options.is_regex = false
options.pos = :after
options.next_line = false
options.brackets = nil
options.files = nil
options.verbose = false
options.force = false

opts = OptionParser.new do |opts|

  # insertfile --insertion="abcdefg" --search=yyy --regex --pos=before --next-line --brackets=[] --left-bracket=[ --right-bracket=] --force  xxx.php

  opts.banner = "Usage: insertfile.rb [options] filename"

  opts.separator ""
  opts.separator "Specific options:"

  opts.on("--insertion [INSERTION]", "Insertion") do |insertion|
    options.insertion = insertion
  end


  opts.on("--search [STRING]", "Search string") do |string|
    options.search = string.force_encoding 'UTF-8'
  end


  opts.on("-e", "--regex", "Search via regex match") do |e|
    options.is_regex = e
  end

  opts.on("--pos [before|after]", [:before, :after],
          "Select insert position (before, after)") do |pos|
    options.pos = pos
  end

  opts.on("--[no-]next-line", "Insert at next line position") do |next_line|
    options.next_line = next_line
  end

  opts.on("--brackets [BRACKETS]", "Insert between the brackets after search position") do |brackets|
    options.brackets = [
      brackets[0..brackets.length/2-1],
      brackets[brackets.length/2..-1]
    ]
  end

  opts.on("--left-bracket [LEFT BRACKET]", "Specify the left bracket") do |bracket|
    options.brackets[0] = bracket
  end

  opts.on("--right-bracket [RIGHT BRACKET]", "Specify the right bracket") do |bracket|
    options.brackets[1] = bracket
  end

  opts.on("-f", "--force", "Insert although exsists") do |force|
    options.force = force
  end


  opts.separator ""
  opts.separator "Common options:"

  opts.on("-v", "--[no-]verbose", "Run verbosely") do |v|
    options.verbose = v
  end

  opts.on_tail("-h", "--help", "Show this message") do
    puts opts
    exit
  end
end

options.files = opts.parse!(ARGV)

if options.is_regex
  options.search = %r[#{options.search}]
else
  options.search = /#{Regexp.escape(options.search)}/
end


if options.verbose
  pp options
end

if options.files.empty?
  puts opts
  exit 1
end



# helper function



class Regexp
  def each_match(str, start = 0)
    while (matchdata = self.match(str, start))
      yield matchdata
      start = matchdata.end(0)
    end
  end
end

def find_brackets_pos(content, begin_pos = 0, nextline = true, left_bracket = '[', right_bracket = ']')

  # inner method find_brackets_pos without finding line
  inner = ->() {

    stack = []
    re = /#{Regexp.escape(left_bracket)}|#{Regexp.escape(right_bracket)}/

    re.each_match(content, begin_pos) {|m|
      # push if found left bracket
      # pop if found right bracket
      # return if match all left bracket
      if m.to_s == left_bracket
        stack.push m.end(0)
      elsif stack.length > 0
        last = stack.pop
        return last, m.begin(0) if stack.length == 0
      else
        return nil, nil
      end
    }

  }

  if nextline
    start_pos, end_pos = inner.call

    unless start_pos.nil? || end_pos.nil?
      nextline_after_start_pos = content.index("\n", start_pos)
      nextline_before_end_pos = content.rindex("\n", end_pos)

      return nextline_after_start_pos+1, nextline_before_end_pos if (start_pos...end_pos) === nextline_after_start_pos && (start_pos...end_pos) === nextline_before_end_pos
    end

  else
    return inner.call
  end
end

def replace!(options, regexp, string, next_line, brackets = nil, force = false)

  content = File.read(options.files.first)
  need_replace = force || !content.include?(options.insertion)

  if need_replace

    if brackets.nil?

      if options.next_line
        # 1. find start pos with regexp
        start_pos = content.index regexp
        return false if start_pos.nil?

        # 2. find line pos
        next_line = (options.pos == :after) ? (content.index "\n", start_pos) + 1 : (content.rindex "\n", start_pos)
        content.insert next_line, string unless next_line.nil?
      else
        string = if options.pos == :after
                    '\0' + string
                  else
                    string + '\0'
                  end
        content.gsub!(regexp, string)
      end
    else

      # 1. find start pos with regexp
      start_pos = content.index regexp
      return false if start_pos.nil?

      # 2. find brackets pos
      left_bracket, right_bracket = brackets
      left_pos, right_pos = find_brackets_pos(content, start_pos, next_line, left_bracket, right_bracket)
      return false if left_pos.nil? || right_pos.nil?

      # 3. insert string into content
      content.insert left_pos, string if (options.pos == :before)
      content.insert right_pos, string if (options.pos == :after)

    end

    File.open(options.files.first, "wb") { |file| file.write(content) }

    return true

  end

  return true

end


exit 1 unless replace!(options, options.search, options.insertion, options.next_line, options.brackets, options.force)









