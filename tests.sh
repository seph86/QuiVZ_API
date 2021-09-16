#!/bin/bash

# ============================================================
# This is a test script to verify api functions are functional
# ============================================================

# file locations
cookies_file="$(dirname -- "$0")/cookies"
output_file="$(dirname -- "$0")/output"

# set default options
host="127.0.0.1"
port="8080"
protocol="http://"
verbose=false
output=false
retain_cookies=false

user="65a6fc4a8551e48fd5a9fcafe9cfb5a35df0782551d870951ec6457e7dee0924"
pass="StrongPassword01"

test_count=0
test_success=0

# print help message
help() {
  echo "${0##*/}: usage"
  echo "-h        : Shows this usage"
  echo "--help    : Same as -h"
  echo "-H [ip]   : Set target host.  Default localhost"
  echo "-p [port] : Set target port.  Default 8080"
  echo "-s        : Set https mode"
  echo "-v        : Print more sh*t"
  echo "-r        : Print json responses"
  echo "-k        : Keep cookie data, do not delete after script is finished"
  echo "-t        : Are you a teapot?"
}

# test curl response against expected value
test() {
  # Print output if there is any
  if [[ $verbose = "true" || $output = "true" ]]; then
    echo -n "Output = "
    cat $output_file
  fi

  # Increment counters and print result
  ((test_count++))
  echo -n "Done.  Expected $1 got $2 - "
  if [[ $1 == $2 ]]; then
    echo -e "\e[32m✓\e[39m"
    ((test_success++))
  else 
    echo -e "\e[31mX\e[39m"
  fi

  # pretty output by putting a empty line at the end 
  if [[ $verbose = "true" || $output = "true" ]]; then echo "" ; fi
}

# Process args
while (( $# )); do
  case $1 in
    -h|--help)
      help 
      exit 1
      ;;
    -p)
      port=$2
      shift 1
      ;;
    -H)
      host=$2
      shift 1
      ;;
    -s)
      protocol="https://"
      ;;
    -v)
      verbose=true
      ;;
    -r)
      output=true
      ;;
    -k)
      retain_cookies=true
      ;;
    -t)
      curl $([[ $verbose = "true" ]] && echo "-v") -c $cookies_file -b $cookies_file -s --header Origin:$protocol$host:$port $protocol$host:$port/teapot/
      exit 418
      ;;
    *)
      echo "Unknown switch: \"$1\", use -h or --help for a list of switches"
      exit 1
      ;;
  esac

  shift

done

# Set command
cmd="curl $([[ $verbose = "true" ]] && echo "-v") -c $cookies_file -b $cookies_file -s -w %{HTTP_CODE} $([[ $verbose = "true" || $output = "true" ]] && echo "-o $output_file" || echo "-o /dev/null" ) --header Origin:$protocol$host:$port $protocol$host:$port"
#echo $cmd"/debug/" ; exit 1

echo "Testing $host:$port .... "
echo "-----------------------------------"

# -----------------------------------------------------------
# Test API integrity
# -----------------------------------------------------------

# Test server rate limiting
echo "Testing rate limiting..."
$cmd > /dev/null
$cmd > /dev/null
$cmd > /dev/null
$cmd > /dev/null
$cmd > /dev/null
$cmd > /dev/null
$cmd > /dev/null
$cmd > /dev/null
$cmd > /dev/null
$cmd > /dev/null
response=`$cmd`
test 429 $response
sleep 1

# Test origin
echo "Testing no header origin"
response=$(curl $([[ $verbose = "true" ]] && echo "-v") -c $cookies_file -b $cookies_file -s -w %{HTTP_CODE} $([[ $verbose = "true" || $output = "true" ]] && echo "-o $output_file" || echo "-o /dev/null" ) $protocol$host:$port)
test 400 $response
sleep 0.5

# Test invalid origin
echo "Testing invalid origin"
response=$(curl $([[ $verbose = "true" ]] && echo "-v") -c $cookies_file -b $cookies_file -s -w %{HTTP_CODE} $([[ $verbose = "true" || $output = "true" ]] && echo "-o $output_file" || echo "-o /dev/null" ) $protocol$host:$port --header Origin:http://example.com)
test 400 $response
sleep 0.5


# -----------------------------------------------------------
# Test functions
# -----------------------------------------------------------

# Test no action and get sesssion id
echo "Testing no action to API..."
response=`$cmd`
test 400 $response
sleep 0.5

# Testing old function
echo "Testing delay function..."
response=`$cmd/delay`
test 400 $response
sleep 0.5

# Test login, no credentials
echo "Testing login with no credentials..."
response=`$cmd/user/login`
test 400 $response
sleep 0.5

# Test login, wrong password
echo "Testing login, wrong password..."
response=`$cmd/user/login -d "uuid=$user&password=wrongpassword"`
test 400 $response
sleep 0.5

# Test logout, not logged in
echo "Testing logout, not logged in..."
response=`$cmd/user/logout`
test 400 $response
sleep 0.5



# ----------------------------------------------
# functions after logged in
# ----------------------------------------------

# Test login, correct password
echo "Testing login, correct password..."
response=`$cmd/user/login -d "uuid=$user&password=$pass"`
test 200 $response
sleep 0.5

# Test logout, user logged in
echo "Testing logout"
response=`$cmd/user/logout`
test 200 $response
sleep 0.5


# Final statistics
echo ""
echo "Total tests: $test_count"
echo -n "Successful: $test_success "
printf %.0f%%\\n "$((10**3 * $test_success / $test_count ))e-1"


#cleanup
if [[ -f $cookies_file && $retain_cookies = "false" ]]; then
  rm $cookies_file
fi
if [ -f $output_file ]; then
  rm $output_file
fi
