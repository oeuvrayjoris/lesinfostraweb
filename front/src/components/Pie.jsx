import React from 'react';
import { ResponsivePie } from '@nivo/pie';

export default ({ repositories }) => (
    <ResponsivePie
        data={
            repositories
        }
        margin={{
            top: 40,
            right: 80,
            bottom: 80,
            left: 80
        }}
        innerRadius={0.5}
        padAngle={0.7}
        cornerRadius={3}
        //colors={["#97e3d5", "#e25c3b"]}
        //colorBy="id"
        borderColor="inherit:darker(0.6)"
        radialLabelsSkipAngle={10}
        radialLabelsTextXOffset={6}
        radialLabelsTextColor="#333333"
        radialLabelsLinkOffset={0}
        radialLabelsLinkDiagonalLength={16}
        radialLabelsLinkHorizontalLength={24}
        radialLabelsLinkStrokeWidth={1}
        radialLabelsLinkColor="inherit"
        slicesLabelsSkipAngle={10}
        slicesLabelsTextColor="#333333"
        animate={true}
        motionStiffness={90}
        motionDamping={15}
        legends={[
            {
                "anchor": "bottom",
                "direction": "row",
                "translateY": 56,
                "itemWidth": 100,
                "itemHeight": 14,
                "symbolSize": 14,
                "symbolShape": "circle"
            }
        ]}
    />
);