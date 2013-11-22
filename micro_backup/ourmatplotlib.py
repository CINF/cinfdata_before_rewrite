#!/usr/bin/python

"""
This file is part of the CINF Data Presentation Website
Copyright (C) 2012 Robert Jensen, Thomas Andersen and Kenneth Nielsen

The CINF Data Presentation Website is free software: you can
redistribute it and/or modify it under the terms of the GNU
General Public License as published by the Free Software
Foundation, either version 3 of the License, or
(at your option) any later version.

The CINF Data Presentation Website is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License
along with The CINF Data Presentation Website.  If not, see
<http://www.gnu.org/licenses/>.
"""

from optparse import OptionParser
import sys
import hashlib

# set HOME environment variable to a directory the httpd server can write to
import os
os.environ[ 'HOME' ] = '/var/www/cinfdata/figures'
# System-wide ctypes cannot be run by apache... strange...
sys.path.insert(1, '/var/www/cinfdata')

# Matplotlib must be imported before MySQLdb (in dataBaseBackend), otherwise we
# get an ugly error
import matplotlib
#matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.transforms as mtransforms
import matplotlib.dates as mdates

# Import our own classes
#from databasebackend import dataBaseBackend
from common import Color, GMT1

class Plot():
    """This class is used to generate the figures for the plots."""
    
    def __init__(self, options, ggs):
        """ Description of init """
        
        self.o = options
        self.ggs = ggs
        
        # Set the image format to standard, overwite with ggs value and again
        # options value if it exits
        if self.o['image_format'] == '':
            self.image_format = self.ggs['image_format']
        else:
            self.image_format = self.o['image_format']

        # Default values for matplotlib plots (names correspond to ggs names)
        mpl_settings = {'width': 900,
                        'height': 600,
                        'title_size': '24',
                        'xtick_labelsize': '12',
                        'ytick_labelsize': '12',
                        'legend_fontsize': '10',
                        'label_fontsize': '16',
                        'linewidth': 1.0,
                        'grid': False}
        
        # Owerwrite defaults with gs values and convert to appropriate types
        for key, value in mpl_settings.items():
            try:
                mpl_settings[key] = type(value)(self.ggs['matplotlib_settings'][key])
            except KeyError:
                pass
        
        # Write some settings to pyplot
        rc_temp = {'figure.figsize': [float(mpl_settings['width'])/100,
                                      float(mpl_settings['height'])/100],
                   'axes.titlesize': mpl_settings['title_size'],
                   'xtick.labelsize': mpl_settings['xtick_labelsize'],
                   'ytick.labelsize': mpl_settings['ytick_labelsize'],
                   'legend.fontsize': mpl_settings['legend_fontsize'],
                   'axes.labelsize': mpl_settings['label_fontsize'],
                   'lines.linewidth': mpl_settings['linewidth'],
                   'axes.grid': mpl_settings['grid']
                   }
        plt.rcParams.update(rc_temp)
                                                        
        # Plotting options
        self.maxticks=15
        self.tz = GMT1()
        self.right_yaxis = len(self.o['right_plotlist']) > 0
        self.measurement_count = None
 
        # object to give first good color, and then random colors
        self.c = Color()

    def new_plot(self, data, plot_info, measurement_count):
        """ Form a new plot with the given data and info """
        self.measurement_count = sum(measurement_count)
        self._init_plot()
        self._plot(data)
        self._zoom_and_flip()
        self._title_and_labels(plot_info)
        self._save(plot_info)

    def _init_plot(self):
        """ Initialize plot """
        self.fig = plt.figure(1)

        self.ax1 = self.fig.add_subplot(111)
        if self.right_yaxis:
            self.ax2 = self.ax1.twinx()

        if self.o['left_logscale']:
            self.ax1.set_yscale('log')
        if self.right_yaxis and self.o['right_logscale']:
            self.ax2.set_yscale('log')

    def _plot(self, data):
        """ Determine the type of the plot and make the appropriate plot by use
        of the functions:
          _plot_dateplot
          _plot_xyplot
        """
        if self.ggs['default_xscale'] == 'dat':
            self._plot_dateplot(data)
        else:
            self._plot_xyplot(data)

    def _plot_dateplot(self, data):
        """ Make the date plot """
        # Rotate datemarks on xaxis
        self.ax1.set_xticklabels([], rotation=25, horizontalalignment='right')
        # Left axis
        for dat in data['left']:
            # Form legend
            if dat['lgs'].has_key('legend'):
                legend = dat['lgs']['legend']
            else:
                legend = None
            # Plot
            if len(dat['data']) > 0:
                self.ax1.plot_date(mdates.epoch2num(dat['data'][:,0]),
                                   dat['data'][:,1],
                                   label=legend,
                                   xdate=True,
                                   color=self.c.get_color(),
                                   tz=self.tz,
                                   fmt='-')
        # Right axis
        for dat in data['right']:
            # Form legend
            if dat['lgs'].has_key('legend'):
                legend = dat['lgs']['legend']
            else:
                legend = None
            # Plot
            if len(dat['data']) > 0:
                self.ax2.plot_date(mdates.epoch2num(dat['data'][:,0]),
                                   dat['data'][:,1],
                                   label=legend,
                                   xdate=True,
                                   color=self.c.get_color(),
                                   tz=self.tz,
                                   fmt='-')
        # No data
        if self.measurement_count == 0:
            y = 0.00032 if self.o['left_logscale'] is True else 0.5
            self.ax1.text(0.5, y, 'No data', horizontalalignment='center',
                          verticalalignment='center', color='red', size=60)

    def _plot_xyplot(self, data):
        # Left axis
        for dat in data['left']:
            # Form legend
            if dat['lgs'].has_key('legend'):
                legend = dat['lgs']['legend']
            else:
                legend = None
            # Plot
            if len(dat['data']) > 0:
                self.ax1.plot(dat['data'][:,0],
                              dat['data'][:,1],
                              '-',
                              label=legend,
                              color=self.c.get_color(),
                              )
        # Right axis
        for dat in data['right']:
            # Form legend
            if dat['lgs'].has_key('legend'):
                legend = dat['lgs']['legend']
            else:
                legend = None
            # Plot
            if len(dat['data']) > 0:
                self.ax2.plot(dat['data'][:,0],
                              dat['data'][:,1],
                              '-',
                              label=legend,
                              color=self.c.get_color()
                              )
        # No data
        if self.measurement_count == 0:
            y = 0.00032 if self.o['left_logscale'] is True else 0.5
            self.ax1.text(0.5, y, 'No data', horizontalalignment='center',
                          verticalalignment='center', color='red', size=60)

    def _zoom_and_flip(self):
        """ Apply the y zooms.
        NOTE: self.ax1.axis() return a list of bounds [xmin,xmax,ymin,ymax] and
        we reuse x and replace y)
        """
        # Left axis 
        if self.o['left_yscale_bounding'] is not None:
            self.ax1.axis(self.ax1.axis()[0:2] + self.o['left_yscale_bounding'])
        # Right axis
        if self.right_yaxis and self.o['right_yscale_bounding'] is not None:
            self.ax2.axis(self.ax2.axis()[0:2] + self.o['right_yscale_bounding'])

        if self.o['flip_x']:
            self.ax1.axis((self.ax1.axis()[1], self.ax1.axis()[0]) + self.ax1.axis()[2:4])

    def _title_and_labels(self, plot_info):
        """ Put title and labels on the plot """
        # xlabel
        if plot_info.has_key('xlabel'):
            label = plot_info['xlabel']
            if plot_info['xlabel_addition'] != '':
                label += '\n' + plot_info['xlabel_addition']
            self.ax1.set_xlabel(label)
        if self.o['xlabel'] != '':  # Manual override
            self.ax1.set_xlabel(r'{0}'.format(self.o['xlabel']))
        # Left ylabel
        if plot_info.has_key('left_ylabel'):
            label = plot_info['left_ylabel']
            if plot_info['y_left_label_addition'] != '':
                label += '\n' + plot_info['y_left_label_addition']
            self.ax1.set_ylabel(label, multialignment='center')
        if self.o['left_ylabel'] != '':  # Manual override
            self.ax1.set_ylabel(self.o['left_ylabel'], multialignment='center')
        # Right ylabel
        if self.right_yaxis and plot_info.has_key('right_ylabel'):
            label = plot_info['right_ylabel']
            if plot_info['y_right_label_addition'] != '':
                label += '\n' + plot_info['y_right_label_addition']
            self.ax2.set_ylabel(label, multialignment='center', rotation=270)
            if self.o['right_ylabel'] != '':  # Manual override
                self.ax2.set_ylabel(self.o['right_ylabel'],
                                    multialignment='center', rotation=270)
        # Title
        if plot_info.has_key('title'):
            self.ax1.set_title(plot_info['title'], y=1.03)
        if self.o['title'] != '':
            # experiment with 'r{0}'.form .. at some time
            self.ax1.set_title('{0}'.format(self.o['title']), y=1.03)
        # Legends
        if self.measurement_count > 0:
            ax1_legends = self.ax1.get_legend_handles_labels()
            if self.right_yaxis:
                ax2_legends = self.ax2.get_legend_handles_labels()
                for color, text in zip(ax2_legends[0], ax2_legends[1]):
                    ax1_legends[0].append(color)
                    ax1_legends[1].append(text)
                    
            # loc for locations, 0 means 'best'. Why that isn't deafult I
            # have no idea
            self.ax1.legend(ax1_legends[0], ax1_legends[1], loc=0)

    def _save(self, plot_info):
        """ Save the figure """
        # The tight method only works if there is a title (it caps of parts of
        # the axis numbers, therefore this hack, this may also become a problem
        # for the other edges of the figure if there are no labels)
        tight = ''
        if plot_info.has_key('title'):
            tight = 'tight'
        # For some wierd reason we cannot write directly to sys.stdout when it
        # is a pdf file, so therefore we use a the StringIO object workaround
        if self.o['image_format'] == 'pdf':
            import StringIO
            out = StringIO.StringIO()
            self.fig.savefig(out, bbox_inches=tight, pad_inches=0.03,
                             format=self.o['image_format'])
            sys.stdout.write(out.getvalue())
        else:
            self.fig.savefig(sys.stdout, bbox_inches=tight, pad_inches=0.03,
                             format=self.o['image_format'])
